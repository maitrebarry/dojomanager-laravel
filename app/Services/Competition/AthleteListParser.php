<?php

namespace App\Services\Competition;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Lit une liste d'athlètes (Excel, CSV ou copier-coller) et la ramène à des lignes
 * normalisées. Seuls nom et prénom sont obligatoires : le poids peut venir à la pesée.
 * Les erreurs sont rendues ligne par ligne pour être corrigées dans l'écran d'aperçu.
 *
 * Colonnes reconnues par leur titre (accents et casse ignorés) ; sans ligne de titre,
 * on suit l'ordre choisi à l'import (ORDERS). Le texte collé peut aussi être séparé par
 * de simples espaces, comme celui d'une liste manuscrite lue par Google Lens :
 * « 1. TRAORE Awa 15 ans ».
 */
class AthleteListParser
{
    public const COLUMNS = ['nom', 'prenom', 'poids', 'sexe', 'naissance', 'club', 'groupe'];

    /** Ordres de colonnes proposés pour une liste sans ligne de titres. */
    public const ORDERS = [
        'modele' => ['nom', 'prenom', 'poids', 'sexe', 'naissance', 'club'],
        'nom_prenom_age' => ['nom', 'prenom', 'naissance'],
        'nom_prenom_age_poids' => ['nom', 'prenom', 'naissance', 'poids'],
        'nom_prenom_age_club' => ['nom', 'prenom', 'naissance', 'club'],
        'prenom_nom_age' => ['prenom', 'nom', 'naissance'],
    ];

    /**
     * Mots qui désignent chaque colonne dans une ligne de titres (accents et casse ignorés),
     * ex. « Date de naissance ou âge », « Poids (kg) », « Prénom(s) ». Mot entier, sauf
     * terminé par * (début de mot). Le prénom est testé avant le nom.
     */
    private const HEADER_WORDS = [
        'groupe' => ['groupe', 'tranche d age', 'categorie d age'],
        'prenom' => ['prenom', 'first name'],
        'nom' => ['nom', 'name'],
        'poids' => ['poids', 'kg', 'weight'],
        'sexe' => ['sexe', 'genre', 'sex', 'm/f'],
        'naissance' => ['naiss*', 'age', 'ne le', 'nee le', 'annee'],
        'club' => ['club', 'salle', 'dojang', 'equipe', 'ligue'],
    ];

    /** @return array<int, array> */
    public function fromUpload(UploadedFile $file, string $order = 'modele'): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'], true)) {
            $reader = new Csv();
            $reader->setInputEncoding(Csv::GUESS_ENCODING);
            $reader->setDelimiter($this->guessDelimiter((string) file_get_contents($file->getRealPath())) ?? ';');
            $sheet = $reader->load($file->getRealPath())->getActiveSheet();
        } else {
            $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
        }

        $rows = [];
        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $value = $cell->getValue();
                // Une date Excel arrive sous forme de nombre de jours : on la convertit.
                if (is_numeric($value) && ExcelDate::isDateTime($cell)) {
                    $value = ExcelDate::excelToDateTimeObject($value)->format('d/m/Y');
                }
                $cells[] = trim((string) $value);
            }
            $rows[] = $cells;
        }

        return $this->normalize($rows, $order);
    }

    /**
     * PDF créé par un logiciel (Word, Excel…) : son texte est lu puis traité comme une liste
     * collée. Un PDF scanné ou photographié n'a pas de texte : PdfWithoutTextException.
     */
    public function fromPdf(UploadedFile $file, string $order = 'modele'): array
    {
        // Chaque morceau de texte a sa position : même hauteur = même ligne, de gauche à
        // droite = colonnes (séparées par des tabulations, comme un collage depuis Excel).
        $lines = [];
        foreach ((new \Smalot\PdfParser\Parser())->parseFile($file->getRealPath())->getPages() as $page) {
            $rows = [];
            foreach ($page->getDataTm() as [$matrix, $chunk]) {
                if (trim($chunk) === '') {
                    continue;
                }
                $y = (float) $matrix[5];
                $key = null;
                foreach (array_keys($rows) as $existing) {
                    if (abs($existing - $y) <= 2.5) {
                        $key = $existing;
                        break;
                    }
                }
                $rows[$key ?? (string) $y][] = [(float) $matrix[4], trim($chunk)];
            }
            krsort($rows, SORT_NUMERIC); // haut de page d'abord

            // Titre de colonne écrit sur deux lignes (« Poids » / « (kg) ») : les lignes sans
            // chiffre très proches en hauteur forment une seule ligne de titres.
            $merged = [];
            $previousY = null;
            foreach ($rows as $y => $cells) {
                $hasDigit = preg_match('/\d/', implode('', array_column($cells, 1)));
                $last = array_key_last($merged);
                if ($last !== null && !$hasDigit && !$merged[$last]['digit'] && $previousY - (float) $y <= 7) {
                    $merged[$last]['cells'] = array_merge($merged[$last]['cells'], $cells);
                } else {
                    $merged[] = ['cells' => $cells, 'digit' => (bool) $hasDigit];
                }
                $previousY = (float) $y;
            }

            foreach ($merged as $row) {
                $cells = $row['cells'];
                usort($cells, fn ($a, $b) => $a[0] <=> $b[0]);
                // Morceaux à la même position horizontale : une seule case.
                $columns = [];
                foreach ($cells as [$x, $chunk]) {
                    $last = array_key_last($columns);
                    if ($last !== null && $x - $columns[$last][0] < 6) {
                        $columns[$last][1] .= ' ' . $chunk;
                    } else {
                        $columns[] = [$x, $chunk];
                    }
                }
                $lines[] = implode("\t", array_column($columns, 1));
            }
        }

        if ($lines === []) {
            throw new PdfWithoutTextException();
        }

        return $this->fromText(implode("\n", $lines), $order);
    }

    /** Texte collé depuis Excel (tabulations), séparé par « ; » / « , », ou par des espaces. */
    public function fromText(string $text, string $order = 'modele'): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', trim($text)) ?: []), fn ($l) => $l !== ''));

        // Titre du document avant le tableau (PDF, liste recopiée) : on part de la ligne de
        // titres des colonnes si elle se trouve parmi les premières lignes.
        foreach (array_slice($lines, 0, 10, true) as $index => $line) {
            if ($index > 0 && !preg_match('/\d/', $line) && count($this->headerMap(preg_split('/\t|;|\s/u', $line)) ?? []) >= 2) {
                $lines = array_slice($lines, $index);
                break;
            }
        }
        $text = implode("\n", $lines);
        $delimiter = $this->guessDelimiter($text);

        if ($delimiter === null) {
            return $this->fromSpacedLines($lines, $order);
        }

        return $this->normalize(array_map(
            fn ($line) => array_map('trim', explode($delimiter, $line)),
            $lines
        ), $order);
    }

    /**
     * Lignes séparées par des espaces : les mots forment nom et prénom (le prénom peut
     * en compter plusieurs), les nombres finaux l'âge et/ou le poids dans l'ordre choisi,
     * une lettre M/F seule le sexe. La numérotation (« 1. », « 2) ») et « ans » sont ignorés.
     */
    private function fromSpacedLines(array $lines, string $order): array
    {
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));
        if ($lines === []) {
            return [];
        }

        $columns = self::ORDERS[$order] ?? self::ORDERS['modele'];
        $firstLine = 1;
        $header = $this->headerMap(preg_split('/\s+/', $lines[0]));
        if ($header !== null) {
            asort($header);
            $columns = array_keys($header);
            array_shift($lines);
            $firstLine = 2;
        }
        $numberColumns = array_values(array_filter($columns, fn ($c) => in_array($c, ['naissance', 'poids'], true)));
        $prenomFirst = array_search('prenom', $columns, true) < array_search('nom', $columns, true);

        $result = [];
        foreach ($lines as $i => $text) {
            // Phrase (titre, pied de page « Page 1/2 »…) plutôt qu'un athlète : ignorée.
            if (preg_match('/^page\s+\d+/iu', $text) || (str_word_count($text) > 6 && !preg_match('/\d/', $text))) {
                continue;
            }
            $text = preg_replace('/^\s*\d{1,3}\s*[.)\-:°]\s*/u', '', $text);
            $text = preg_replace(['/(\d)\s*ans?\b/iu', '/\bans?\b/iu'], ['$1', ''], $text);
            $words = [];
            $numbers = [];
            $sexe = '';
            foreach (preg_split('/\s+/', trim($text)) as $token) {
                if (preg_match('#^\d{1,3}([.,]\d+)?$|^\d{1,2}[/.-]\d{1,2}[/.-]\d{2,4}$|^\d{4}$#', $token)) {
                    $numbers[] = $token;
                } elseif (in_array(mb_strtoupper($token), ['M', 'F', 'G'], true) && $words !== []) {
                    $sexe = mb_strtoupper($token) === 'F' ? 'F' : 'M';
                } elseif ($token !== '') {
                    $words[] = $token;
                }
            }

            [$nom, $prenom] = $this->splitName($words, $prenomFirst);
            $line = ['line' => $i + $firstLine, 'nom' => $nom, 'prenom' => $prenom, 'poids' => '', 'sexe' => $sexe, 'naissance' => '', 'club' => ''];
            foreach ($numberColumns as $k => $column) {
                $line[$column] = $numbers[$k] ?? '';
            }
            $result[] = $this->check($line);
        }

        return $result;
    }

    /**
     * Sépare nom et prénom d'une suite de mots. Un seul mot en MAJUSCULES : c'est le nom
     * (habitude des listes). Sinon on suit l'ordre : nom en premier mot, ou en dernier
     * mot quand le prénom vient d'abord (« Moussa Ibrahim DIARRA »).
     *
     * @return array{0: string, 1: string}
     */
    private function splitName(array $words, bool $prenomFirst): array
    {
        $upper = array_keys(array_filter($words, fn ($w) => mb_strlen($w) > 1 && $w === mb_strtoupper($w) && preg_match('/\p{L}/u', $w)));
        if (count($upper) === 1 && count($words) > 1) {
            $nom = $words[$upper[0]];
            unset($words[$upper[0]]);

            return [$nom, implode(' ', $words)];
        }

        $nom = $prenomFirst ? array_pop($words) : array_shift($words);

        return [(string) $nom, implode(' ', $words)];
    }

    /**
     * @param array<int, array<int, string>> $rows
     * @return array<int, array{line: int, nom: string, prenom: string, poids: string, sexe: string, naissance: string, club: string, errors: array}>
     */
    public function normalize(array $rows, string $order = 'modele'): array
    {
        $rows = array_values(array_filter($rows, fn ($cells) => implode('', $cells) !== ''));
        if ($rows === []) {
            return [];
        }

        $map = $this->headerMap($rows[0]);
        $firstLine = 1;
        if ($map !== null) {
            array_shift($rows);
            $firstLine = 2;
        } else {
            $map = array_flip(self::ORDERS[$order] ?? self::ORDERS['modele']);
        }

        $result = [];
        foreach ($rows as $i => $cells) {
            // Titres répétés (en-tête de chaque page d'un PDF) ou ligne à remplir en pointillés.
            $joined = implode(' ', $cells);
            if ((!preg_match('/\d/', $joined) && $this->headerMap($cells) !== null) || preg_match('/[.…_]{4,}/u', $joined)) {
                continue;
            }
            // Titre isolé (nom de la compétition, date… en haut de chaque page) : une seule case
            // alors que le nom et le prénom ont chacun leur colonne.
            if (isset($map['nom'], $map['prenom']) && count(array_filter($cells, fn ($c) => trim($c) !== '')) <= 1) {
                continue;
            }
            $line = ['line' => $i + $firstLine];
            foreach (self::COLUMNS as $column) {
                $line[$column] = isset($map[$column]) ? trim((string) ($cells[$map[$column]] ?? '')) : '';
            }
            // Pas de nom ni de prénom, ou une date à la place du nom : ce n'est pas un athlète.
            if (($line['nom'] === '' && $line['prenom'] === '') || ($line['prenom'] === '' && preg_match('/\d.*\d/', $line['nom']))) {
                continue;
            }
            $result[] = $this->check($line);
        }

        return $result;
    }

    /** Valide et nettoie une ligne (aussi utilisé à la confirmation de l'aperçu). */
    public function check(array $line): array
    {
        $line['nom'] = trim((string) ($line['nom'] ?? ''));
        $line['prenom'] = trim((string) ($line['prenom'] ?? ''));
        $line['club'] = trim((string) ($line['club'] ?? ''));
        $line['groupe'] = trim((string) ($line['groupe'] ?? ''));
        $line['poids'] = str_replace([',', 'kg', 'KG', ' '], ['.', '', '', ''], trim((string) ($line['poids'] ?? '')));
        $line['sexe'] = $this->parseSexe((string) ($line['sexe'] ?? '')) ?? trim((string) ($line['sexe'] ?? ''));
        $line['naissance'] = trim((string) ($line['naissance'] ?? ''));

        $errors = [];
        if ($line['nom'] === '') {
            $errors[] = 'nom manquant';
        }
        if ($line['prenom'] === '') {
            $errors[] = 'prénom manquant';
        }
        // Poids facultatif : sans poids, l'athlète est « à peser ».
        if ($line['poids'] !== '' && (!is_numeric($line['poids']) || (float) $line['poids'] < 10 || (float) $line['poids'] > 200)) {
            $errors[] = 'poids invalide';
        }
        if ($line['sexe'] !== '' && !in_array($line['sexe'], ['M', 'F'], true)) {
            $errors[] = 'sexe à écrire M ou F';
        }
        if ($line['naissance'] !== '' && $this->parseBirth($line['naissance']) === null) {
            $errors[] = 'âge ou date de naissance illisible';
        }
        $line['errors'] = $errors;

        return $line;
    }

    /**
     * Âge (« 15 »), année (« 2009 ») ou date (« 12/03/2009 »).
     *
     * @return array{age: ?int, date_naissance: ?string}|null
     */
    public function parseBirth(string $value): ?array
    {
        $value = trim(str_ireplace(['ans', 'an'], '', $value));
        if ($value === '') {
            return ['age' => null, 'date_naissance' => null];
        }

        if (ctype_digit($value)) {
            $number = (int) $value;
            if ($number >= 3 && $number <= 99) {
                return ['age' => $number, 'date_naissance' => null];
            }
            if ($number >= 1920 && $number <= (int) date('Y')) {
                return ['age' => null, 'date_naissance' => $number . '-01-01'];
            }

            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'd/m/y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $value);
            } catch (\Throwable) {
                continue;
            }
            if ($date && $date->format($format) === $value && $date->year >= 1920 && $date->isPast()) {
                return ['age' => null, 'date_naissance' => $date->toDateString()];
            }
        }

        return null;
    }

    private function parseSexe(string $value): ?string
    {
        $value = $this->simplify($value);

        return match (true) {
            in_array($value, ['m', 'masculin', 'garcon', 'homme', 'h', 'g'], true) => 'M',
            in_array($value, ['f', 'feminin', 'fille', 'femme'], true) => 'F',
            default => null,
        };
    }

    /** Index des colonnes si la 1re ligne est une ligne de titres, sinon null. */
    private function headerMap(array $cells): ?array
    {
        $map = [];
        foreach ($cells as $index => $cell) {
            $label = $this->simplify($cell);
            if ($label === '') {
                continue;
            }
            foreach (self::HEADER_WORDS as $column => $words) {
                if (isset($map[$column])) {
                    continue;
                }
                foreach ($words as $word) {
                    $pattern = str_ends_with($word, '*')
                        ? '/(^|[^a-z])' . preg_quote(rtrim($word, '*'), '/') . '/'
                        : '/(^|[^a-z])' . preg_quote($word, '/') . 's?([^a-z]|$)/';
                    if (preg_match($pattern, $label)) {
                        $map[$column] = $index;
                        continue 3; // une colonne du fichier ne sert qu'une fois
                    }
                }
            }
        }

        return isset($map['nom']) || isset($map['poids']) ? $map : null;
    }

    private function simplify(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'û' => 'u', 'ù' => 'u', 'ç' => 'c', 'ñ' => 'n']);

        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /** Tabulation, « ; » ou « , » selon la 1re ligne ; null si la ligne n'est séparée que par des espaces. */
    private function guessDelimiter(string $text): ?string
    {
        // Une virgule entre deux chiffres (« 52,5 ») est un poids décimal, pas un séparateur.
        $firstLine = preg_replace('/(\d),(\d)/', '$1$2', strtok($text, "\r\n") ?: '');
        $counts = ["\t" => substr_count($firstLine, "\t"), ';' => substr_count($firstLine, ';'), ',' => substr_count($firstLine, ',')];
        arsort($counts);

        return reset($counts) > 0 ? array_key_first($counts) : null;
    }
}

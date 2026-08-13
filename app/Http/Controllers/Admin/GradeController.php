<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesScope;
use App\Http\Controllers\Controller;
use App\Models\Federation;
use App\Models\Grade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GradeController extends Controller
{
    use AuthorizesScope;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->middleware('permission:GRADES_READ')->only(['index']);
        $this->middleware('permission:GRADES_CREATE')->only(['create', 'store']);
        $this->middleware('permission:GRADES_UPDATE')->only(['edit', 'update']);
        $this->middleware('permission:GRADES_DELETE')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $grades = Grade::query()
            ->visibleTo($request->user())
            ->with('federation:id,nom')
            ->when($request->filled('search'), fn ($q) => $q->where('nom_grade', 'like', '%' . $request->query('search') . '%'))
            ->when($request->filled('type'), fn ($q) => $q->where('type_grade', $request->query('type')))
            ->orderBy('type_grade')
            ->orderBy('niveau')
            ->paginate(20)
            ->withQueryString();

        return view('admin.grades.index', [
            'grades' => $grades,
            'page_title' => __('messages.grades.title'),
        ]);
    }

    public function create(): View
    {
        return view('admin.grades.create', [
            'grade' => new Grade(['type_grade' => 'KEUP']),
            'federations' => Federation::visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom']),
            'page_title' => __('messages.grades.add'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Grade::create($this->validateData($request));

        return redirect()->route('admin.settings', ['tab' => 'grades'])
            ->with('success', __('messages.grades.created'));
    }

    public function edit(Grade $grade): View
    {
        $this->guardScope($grade);

        return view('admin.grades.edit', [
            'grade' => $grade,
            'federations' => Federation::visibleTo(request()->user())->orderBy('nom')->get(['id', 'nom']),
            'page_title' => __('messages.grades.edit'),
        ]);
    }

    public function update(Request $request, Grade $grade): RedirectResponse
    {
        $this->guardScope($grade);
        $grade->update($this->validateData($request));

        return redirect()->route('admin.settings', ['tab' => 'grades'])
            ->with('success', __('messages.grades.updated'));
    }

    public function destroy(Grade $grade): RedirectResponse
    {
        $this->guardScope($grade);

        if ($grade->disciples()->exists()) {
            return back()->with('error', __('messages.grades.delete_blocked'));
        }

        $grade->delete();

        return redirect()->route('admin.settings', ['tab' => 'grades'])
            ->with('success', __('messages.grades.deleted'));
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'niveau' => ['required', 'integer', 'min:0'],
            'nom_grade' => ['required', 'string', 'max:120'],
            'ceinture' => ['required', 'string', 'max:60'],
            'federation_id' => ['required', 'exists:federations,id'],
            'type_grade' => ['required', Rule::in(['KEUP', 'DAN'])],
        ]);
    }
}

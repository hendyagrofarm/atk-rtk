<?php

namespace App\Http\Controllers;

use App\Models\ApprovalStep;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Pengaturan urutan tahap approval pengajuan custom. Khusus super admin.
 *
 * Perubahan hanya berlaku untuk pengajuan baru. Pengajuan yang sudah berjalan
 * memakai salinan alur saat pengajuan itu dibuat.
 */
class ApprovalStepController extends Controller
{
    public function index()
    {
        $steps = ApprovalStep::query()
            ->with(['position' => fn ($query) => $query->withCount('users')])
            ->ordered()
            ->get();

        $positions = Position::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('approval-steps.index', [
            'steps' => $steps,
            'positions' => $positions,
            'labels' => ApprovalStep::LABELS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        ApprovalStep::create([
            'step_order' => (int) ApprovalStep::max('step_order') + 1,
            'label' => $validated['label'],
            'position_id' => $validated['position_id'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('approval-steps.index')
            ->with('success', 'Tahap approval berhasil ditambahkan.');
    }

    public function update(Request $request, ApprovalStep $approvalStep)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $approvalStep->update([
            'label' => $validated['label'],
            'position_id' => $validated['position_id'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('approval-steps.index')
            ->with('success', 'Tahap approval berhasil diperbarui.');
    }

    public function destroy(ApprovalStep $approvalStep)
    {
        DB::transaction(function () use ($approvalStep) {
            $approvalStep->delete();

            // Rapatkan nomor urut agar tidak ada celah.
            ApprovalStep::ordered()->get()->each(function (ApprovalStep $step, int $index) {
                $step->update(['step_order' => $index + 1]);
            });
        });

        return redirect()
            ->route('approval-steps.index')
            ->with('success', 'Tahap approval berhasil dihapus.');
    }

    /**
     * Geser tahap ke atas atau ke bawah dengan menukar urutan.
     */
    public function move(Request $request, ApprovalStep $approvalStep)
    {
        $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);

        $neighbor = $request->direction === 'up'
            ? ApprovalStep::where('step_order', '<', $approvalStep->step_order)->orderByDesc('step_order')->first()
            : ApprovalStep::where('step_order', '>', $approvalStep->step_order)->orderBy('step_order')->first();

        if ($neighbor) {
            DB::transaction(function () use ($approvalStep, $neighbor) {
                $mine = $approvalStep->step_order;
                $theirs = $neighbor->step_order;

                // step_order bersifat unik, jadi gunakan nilai sementara saat menukar.
                $approvalStep->update(['step_order' => 0]);
                $neighbor->update(['step_order' => $mine]);
                $approvalStep->update(['step_order' => $theirs]);
            });
        }

        return redirect()->route('approval-steps.index');
    }

    private function rules(): array
    {
        return [
            'label' => ['required', Rule::in(ApprovalStep::LABELS)],
            'position_id' => [
                'required',
                'integer',
                Rule::exists('positions', 'id')->where('is_active', true),
            ],
        ];
    }

    private function messages(): array
    {
        return [
            'label.required' => 'Label tahap wajib dipilih.',
            'label.in' => 'Label tahap tidak valid.',
            'position_id.required' => 'Jabatan wajib dipilih.',
            'position_id.exists' => 'Jabatan tidak ditemukan atau sudah nonaktif.',
        ];
    }
}

<?php

namespace App\Modules\MLStandard\Http\Controllers;

use App\Exports\LevantamentoStdExport;
use App\Http\Controllers\Controller;
use App\Modules\MLStandard\Models\OrcMLstd;
use App\Modules\MLStandard\Models\OrcMLstdItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OrcMLstdController extends Controller
{
    public function index()
    {
        return view('mlstandard::orc-mlstd.index');
    }

    public function levantamento($id)
    {
        $orc = OrcMLstd::findOrFail((int) $id);

        return view('mlstandard::orc-mlstd.levantamento', compact('orc'));
    }

    public function downloadTemplate()
    {
        $path = storage_path('app/templates/modelo_levantamento.xlsx');

        abort_unless(file_exists($path), 404, 'Template nao encontrado.');

        return response()->download($path, 'modelo_levantamento.xlsx');
    }

    public function importLevantamento(Request $request, int $id)
    {
        $orc = OrcMLstd::findOrFail($id);

        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:xlsx'],
        ], [
            'arquivo.required' => 'Selecione o arquivo Excel (.xlsx).',
            'arquivo.mimes' => 'O arquivo deve ser do tipo .xlsx',
        ]);

        $filePath = $request->file('arquivo')->getRealPath();

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $norm = function (string $s): string {
            $s = trim($s);
            $s = preg_replace('/\s+/', ' ', $s);
            return mb_strtoupper($s, 'UTF-8');
        };

        $existentes = OrcMLstdItem::query()
            ->where('orc_ml_std_id', $orc->id)
            ->get(['disciplina', 'descricao']);

        $setExistentes = [];
        foreach ($existentes as $e) {
            $key = $norm($e->disciplina) . '|' . $norm((string) $e->descricao);
            $setExistentes[$key] = true;
        }

        $setArquivo = [];

        $maxOrdem = (int) OrcMLstdItem::query()
            ->where('orc_ml_std_id', $orc->id)
            ->max('ordem');

        $ordem = $maxOrdem + 1;

        $itemsToInsert = [];
        $inseridos = 0;
        $ignorados = 0;

        for ($r = 2; $r <= $highestRow; $r++) {
            $disciplina = strtoupper(trim((string) $sheet->getCell("A{$r}")->getValue()));
            $descricao = trim((string) $sheet->getCell("B{$r}")->getValue());

            if ($disciplina === '' && $descricao === '') {
                continue;
            }

            if (! in_array($disciplina, ['ELE', 'TUB'], true)) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                return back()->withErrors([
                    'arquivo' => "Disciplina invalida na linha {$r}. Use apenas ELE ou TUB.",
                ]);
            }

            if ($descricao === '') {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                return back()->withErrors([
                    'arquivo' => "Descricao vazia na linha {$r}.",
                ]);
            }

            $key = $norm($disciplina) . '|' . $norm($descricao);

            if (isset($setExistentes[$key])) {
                $ignorados++;
                continue;
            }

            if (isset($setArquivo[$key])) {
                $ignorados++;
                continue;
            }

            $setArquivo[$key] = true;
            $setExistentes[$key] = true;

            $itemsToInsert[] = [
                'orc_ml_std_id' => $orc->id,
                'ordem' => $ordem++,
                'disciplina' => $disciplina,
                'descricao' => $descricao,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $inseridos++;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if (empty($itemsToInsert)) {
            return redirect()
                ->route('mlstandard.orcamentos.levantamento', ['id' => $orc->id])
                ->with('success', "Import concluido: nenhum item novo encontrado. Ignorados: {$ignorados}.");
        }

        DB::transaction(function () use ($itemsToInsert): void {
            foreach (array_chunk($itemsToInsert, 1000) as $chunk) {
                OrcMLstdItem::insert($chunk);
            }
        });

        return redirect()
            ->route('mlstandard.orcamentos.levantamento', ['id' => $orc->id])
            ->with('success', "Import concluido: {$inseridos} novos itens adicionados. Ignorados: {$ignorados}.");
    }

    public function exportLevantamento(int $id)
    {
        $orc = OrcMLstd::findOrFail($id);

        $filename = "Levantamento_{$orc->numero_orcamento}_rev{$orc->rev}.xlsx";

        return Excel::download(new LevantamentoStdExport($orc->id), $filename);
    }
}

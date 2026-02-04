<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\Models\OrcMLstd;
use App\Models\OrcMLstdItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Exports\LevantamentoStdExport;
use Maatwebsite\Excel\Facades\Excel;

class OrcMLstdController extends Controller
{
    public function index(): View
    {
        return view('orc-mlstd.index');
    }

    public function levantamento($id)
    {
        $orc = OrcMLstd::findOrFail((int) $id);

        return view('orc-mlstd.levantamento', compact('orc'));
    }

    public function downloadTemplate()
    {
        $path = storage_path('app/templates/modelo_levantamento.xlsx');

        abort_unless(file_exists($path), 404, 'Template não encontrado.');

        return response()->download($path, 'modelo_levantamento.xlsx');
    }

    public function importLevantamento(Request $request, int $id)
    {
        $orc = OrcMLstd::findOrFail($id);

        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:xlsx'],
        ], [
            'arquivo.required' => 'Selecione o arquivo Excel (.xlsx).',
            'arquivo.mimes'    => 'O arquivo deve ser do tipo .xlsx',
        ]);

        $filePath = $request->file('arquivo')->getRealPath();

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        // ============================
        // ✅ Helpers de normalização
        // ============================
        $norm = function (string $s): string {
            $s = trim($s);
            $s = preg_replace('/\s+/', ' ', $s); // remove espaços duplicados
            return mb_strtoupper($s, 'UTF-8');
        };

        // ============================
        // ✅ Busca o que já existe no banco (por disciplina)
        // ============================
        $existentes = OrcMLstdItem::query()
            ->where('orc_ml_std_id', $orc->id)
            ->get(['disciplina', 'descricao']);

        // Monta SET: "ELE|DESCRICAO NORMALIZADA" => true
        $setExistentes = [];
        foreach ($existentes as $e) {
            $key = $norm($e->disciplina) . '|' . $norm((string)$e->descricao);
            $setExistentes[$key] = true;
        }

        // ✅ Também evita duplicidade dentro do arquivo importado
        $setArquivo = [];

        // ✅ Ordem começa a partir do maior ordem existente
        $maxOrdem = (int) OrcMLstdItem::query()
            ->where('orc_ml_std_id', $orc->id)
            ->max('ordem');

        $ordem = $maxOrdem + 1;

        $itemsToInsert = [];
        $inseridos = 0;
        $ignorados = 0;

        // ✅ Começa na linha 2 (linha 1 é cabeçalho)
        for ($r = 2; $r <= $highestRow; $r++) {

            $disciplina = strtoupper(trim((string) $sheet->getCell("A{$r}")->getValue()));
            $descricao  = trim((string) $sheet->getCell("B{$r}")->getValue());

            // pula linhas vazias
            if ($disciplina === '' && $descricao === '') {
                continue;
            }

            // valida disciplina
            if (!in_array($disciplina, ['ELE', 'TUB'], true)) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                return back()->withErrors([
                    'arquivo' => "Disciplina inválida na linha {$r}. Use apenas ELE ou TUB."
                ]);
            }

            if ($descricao === '') {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                return back()->withErrors([
                    'arquivo' => "Descrição vazia na linha {$r}."
                ]);
            }

            // ✅ chave normalizada para comparação
            $key = $norm($disciplina) . '|' . $norm($descricao);

            // ✅ se já existe no banco -> ignora
            if (isset($setExistentes[$key])) {
                $ignorados++;
                continue;
            }

            // ✅ se duplicou dentro do arquivo -> ignora
            if (isset($setArquivo[$key])) {
                $ignorados++;
                continue;
            }

            $setArquivo[$key] = true;
            $setExistentes[$key] = true;

            $itemsToInsert[] = [
                'orc_ml_std_id' => $orc->id,
                'ordem'         => $ordem++,
                'disciplina'    => $disciplina,
                'descricao'     => $descricao,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            $inseridos++;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        // ✅ Se nada novo, só retorna sucesso informativo
        if (empty($itemsToInsert)) {
            return redirect()
                ->route('orc-mlstd.levantamento', ['id' => $orc->id])
                ->with('success', "Import concluído: nenhum item novo encontrado. Ignorados: {$ignorados}.");
        }

        DB::transaction(function () use ($itemsToInsert) {
            // ✅ Insert em blocos (safe)
            foreach (array_chunk($itemsToInsert, 1000) as $chunk) {
                OrcMLstdItem::insert($chunk);
            }
        });

        return redirect()
            ->route('orc-mlstd.levantamento', ['id' => $orc->id])
            ->with('success', "Import concluído: {$inseridos} novos itens adicionados. Ignorados: {$ignorados}.");
    }

    public function exportLevantamento(int $id)
    {
        $orc = OrcMLstd::findOrFail($id);

        $filename = "Levantamento_{$orc->numero_orcamento}_rev{$orc->rev}.xlsx";

        return Excel::download(new LevantamentoStdExport($orc->id), $filename);
    }
}

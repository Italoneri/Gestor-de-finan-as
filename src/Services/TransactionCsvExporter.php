<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CategoryType;
use App\Models\Transaction;
use RuntimeException;

/**
 * Semicolon-separated CSV with UTF-8 BOM — the combination Brazilian Excel
 * opens correctly out of the box (comma is the decimal separator here).
 */
final class TransactionCsvExporter
{
    /**
     * @param list<Transaction> $transactions
     */
    public static function export(array $transactions): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new RuntimeException('csv export: cannot open temp stream');
        }

        fwrite($handle, "\u{FEFF}");
        fputcsv($handle, ['Data', 'Descrição', 'Categoria', 'Conta', 'Tipo', 'Valor (R$)'], ';');

        foreach ($transactions as $tx) {
            $sign = $tx->type === CategoryType::Income ? '' : '-';
            fputcsv($handle, [
                br_date($tx->date),
                self::cell($tx->description),
                self::cell($tx->categoryName),
                self::cell($tx->accountName),
                $tx->type->label(),
                $sign . $tx->amount()->formatBr(),
            ], ';');
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Formula-injection guard: spreadsheet apps execute cells starting with
     * = + - @ or tab/CR. A leading apostrophe forces text. Applied only to
     * free-text cells — the amount column is app-formatted and must stay
     * numeric (its leading minus is legitimate).
     */
    private static function cell(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'" . $value : $value;
    }
}

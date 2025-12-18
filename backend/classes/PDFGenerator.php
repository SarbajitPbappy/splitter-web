<?php
/**
 * PDF Generator Class
 * Generates PDF reports for groups
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Group.php';
require_once __DIR__ . '/Expense.php';
require_once __DIR__ . '/Settlement.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// TCPDF is autoloaded, no need for use statement

class PDFGenerator {
    private $db;
    private $group;
    private $expense;
    private $settlement;
    
    public function __construct() {
        $this->db = getDB();
        $this->group = new Group();
        $this->expense = new Expense();
        $this->settlement = new Settlement();
    }
    
    /**
     * Generate PDF report for group
     */
    public function generateReport($groupId) {
        $group = $this->group->getById($groupId);
        if (!$group) {
            throw new Exception('Group not found');
        }
        
        $members = $this->group->getMembers($groupId);
        $expensesData = $this->expense->getGroupExpenses($groupId, 1, 1000); // Get all expenses
        $settlementData = $this->settlement->calculateSettlements($groupId);
        
        // Create PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Splitter');
        $pdf->SetAuthor('Splitter');
        $pdf->SetTitle('Group Report: ' . $group['name']);
        $pdf->SetSubject('Expense Report');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 10, $group['name'], 0, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 5, 'Type: ' . $group['type'], 0, 1, 'C');
        $pdf->Cell(0, 5, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Group Description
        if ($group['description']) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 5, 'Description: ' . $group['description'], 0, 'L');
            $pdf->Ln(5);
        }
        
        // Members
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Members', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        foreach ($members as $member) {
            $pdf->Cell(0, 5, '- ' . $member['name'] . ' (' . $member['email'] . ')', 0, 1);
        }
        $pdf->Ln(5);
        
        // Expenses Summary
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Expenses Summary', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        $totalAmount = 0;
        foreach ($expensesData['expenses'] as $exp) {
            $totalAmount += $exp['amount'];
        }
        
        $pdf->Cell(0, 5, 'Total Expenses: ৳' . number_format($totalAmount, 2), 0, 1);
        $pdf->Cell(0, 5, 'Number of Expenses: ' . count($expensesData['expenses']), 0, 1);
        $pdf->Ln(5);
        
        // Recent Expenses
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Recent Expenses', 0, 1);
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(60, 6, 'Date', 1, 0, 'C');
        $pdf->Cell(40, 6, 'Paid By', 1, 0, 'C');
        $pdf->Cell(40, 6, 'Amount', 1, 0, 'C');
        $pdf->Cell(50, 6, 'Description', 1, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 8);
        foreach (array_slice($expensesData['expenses'], 0, 20) as $exp) {
            $pdf->Cell(60, 5, $exp['expense_date'], 1, 0, 'L');
            $pdf->Cell(40, 5, substr($exp['paid_by_name'], 0, 20), 1, 0, 'L');
            $pdf->Cell(40, 5, '৳' . number_format($exp['amount'], 2), 1, 0, 'R');
            $pdf->Cell(50, 5, substr($exp['description'] ?? '', 0, 25), 1, 1, 'L');
        }
        $pdf->Ln(5);
        
        // Settlement
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Member Balances', 0, 1);
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(80, 6, 'Member', 1, 0, 'C');
        $pdf->Cell(40, 6, 'Paid', 1, 0, 'C');
        $pdf->Cell(40, 6, 'Owed', 1, 0, 'C');
        $pdf->Cell(30, 6, 'Balance', 1, 1, 'C');
        
        $pdf->SetFont('helvetica', '', 9);
        foreach ($settlementData['balances'] as $balance) {
            $pdf->Cell(80, 5, $balance['name'], 1, 0, 'L');
            $pdf->Cell(40, 5, '৳' . number_format($balance['paid'], 2), 1, 0, 'R');
            $pdf->Cell(40, 5, '৳' . number_format($balance['owed'], 2), 1, 0, 'R');
            $balanceText = '৳' . number_format($balance['balance'], 2);
            $pdf->Cell(30, 5, $balanceText, 1, 1, 'R');
        }
        $pdf->Ln(5);
        
        // Settlement Transactions
        if (!empty($settlementData['settlements'])) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Settlement Transactions', 0, 1);
            
            $pdf->SetFont('helvetica', '', 10);
            foreach ($settlementData['settlements'] as $settlement) {
                $text = sprintf(
                    '%s owes %s ৳%s',
                    $settlement['from_name'],
                    $settlement['to_name'],
                    number_format($settlement['amount'], 2)
                );
                $pdf->Cell(0, 5, $text, 0, 1);
            }
        }
        
        // Output PDF
        return $pdf->Output('', 'S'); // Return as string
    }
}


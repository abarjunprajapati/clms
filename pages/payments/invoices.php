<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin', 'finance']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    $invoices = db_fetch_all($conn, "SELECT i.*, c.contractor_name FROM contractor_invoices i JOIN contractors c ON i.contractor_id = c.id ORDER BY i.created_at DESC");
    
    $stats = [
        'pending' => db_count($conn, "SELECT COUNT(*) FROM contractor_invoices WHERE status='pending'"),
        'approved' => db_count($conn, "SELECT COUNT(*) FROM contractor_invoices WHERE status='approved'"),
        'paid' => db_count($conn, "SELECT COUNT(*) FROM contractor_invoices WHERE status='paid'"),
        'total_value' => db_single($conn, "SELECT SUM(net_payable) as total FROM contractor_invoices")['total'] ?? 0
    ];
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-file-invoice-dollar" style="color:#10b981;margin-right:10px;"></i> Commercial Governance</h2>
        <!-- <p class="page-subtitle">Invoice management, tax compliance (GST/TDS), and payment approval workflows.</p> -->
      </div>
      <div class="action-buttons">
        <button class="btn btn-primary" onclick="document.getElementById('invoiceModal').style.display='flex'"><i class="fas fa-plus"></i> New Invoice</button>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;">
      <div class="card glass" style="padding:18px;text-align:center;">
        <div style="font-size:28px;font-weight:800;color:#f59e0b;"><?= $stats['pending'] ?></div>
        <div style="font-size:12px;opacity:0.6;">Pending Approval</div>
      </div>
      <div class="card glass" style="padding:18px;text-align:center;">
        <div style="font-size:28px;font-weight:800;color:#6366f1;"><?= $stats['approved'] ?></div>
        <div style="font-size:12px;opacity:0.6;">Approved</div>
      </div>
      <div class="card glass" style="padding:18px;text-align:center;">
        <div style="font-size:28px;font-weight:800;color:#10b981;"><?= $stats['paid'] ?></div>
        <div style="font-size:12px;opacity:0.6;">Paid</div>
      </div>
      <div class="card glass" style="padding:18px;text-align:center;">
        <div style="font-size:24px;font-weight:800;color:#10b981;">₹<?= number_format($stats['total_value']/100000, 1) ?>L</div>
        <div style="font-size:12px;opacity:0.6;">Total Payable</div>
      </div>
    </div>

    <div class="card glass" style="padding:0;overflow:hidden;">
        <div style="padding:16px;border-bottom:1px solid rgba(255,255,255,0.05);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;font-size:16px;">Contractor Invoices</h3>
        </div>
        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Contractor</th>
                        <th>Gross</th>
                        <th>GST</th>
                        <th>TDS</th>
                        <th>Net Payable</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:40px;opacity:0.5;">No invoices found.</td></tr>
                    <?php else: ?>
                        <?php foreach($invoices as $i): ?>
                            <tr>
                                <td><?= htmlspecialchars($i['invoice_number']) ?></td>
                                <td><?= htmlspecialchars($i['contractor_name']) ?></td>
                                <td>₹<?= number_format($i['gross_amount'], 2) ?></td>
                                <td>₹<?= number_format($i['gst_amount'], 2) ?></td>
                                <td>₹<?= number_format($i['tds_amount'], 2) ?></td>
                                <td style="font-weight:bold;">₹<?= number_format($i['net_payable'], 2) ?></td>
                                <td><span class="status-pill status-<?= $i['status'] ?>"><?= ucfirst($i['status']) ?></span></td>
                                <td>
                                    <button class="btn-icon" title="View Workflow"><i class="fas fa-project-diagram"></i></button>
                                    <?php if ($i['status'] == 'pending'): ?>
                                        <button class="btn btn-sm btn-success" style="padding:4px 8px;font-size:11px;">Approve</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- New Invoice Modal -->
    <div id="invoiceModal" class="glass" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
        <div class="card" style="width:600px; background:#1e293b; border:1px solid rgba(255,255,255,0.1); padding:24px; border-radius:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0;">Raise New Invoice</h3>
                <button onclick="document.getElementById('invoiceModal').style.display='none'" style="background:none; border:none; color:#fff; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form action="../../api/payments/create_invoice.php" method="POST">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-size:13px; opacity:0.7;">Select Contractor</label>
                        <select name="contractor_id" class="form-control" style="width:100%; background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1);">
                            <?php
                            $conts = db_fetch_all($conn, "SELECT id, contractor_name FROM contractors");
                            foreach($conts as $c) echo "<option value='{$c['id']}'>{$c['contractor_name']}</option>";
                            ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-size:13px; opacity:0.7;">Invoice Number</label>
                        <input type="text" name="invoice_number" class="form-control" required style="width:100%; background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1);">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-size:13px; opacity:0.7;">Gross Amount (₹)</label>
                        <input type="number" step="0.01" name="gross_amount" class="form-control" required style="width:100%; background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1);">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-size:13px; opacity:0.7;">GST Amount (₹)</label>
                        <input type="number" step="0.01" name="gst_amount" class="form-control" required style="width:100%; background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1);">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-size:13px; opacity:0.7;">TDS Deduction (₹)</label>
                        <input type="number" step="0.01" name="tds_amount" class="form-control" required style="width:100%; background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1);">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-size:13px; opacity:0.7;">Invoice Date</label>
                        <input type="date" name="invoice_date" class="form-control" required style="width:100%; background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.1);">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:12px;">Submit Invoice for Approval</button>
            </form>
        </div>
    </div>
    <?php
}

renderLayout('Commercial Governance', 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>

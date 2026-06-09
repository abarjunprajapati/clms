import re

with open("c:/xampp/htdocs/clms/pages/contractor/annexure-2a.php", "r", encoding="utf-8") as f:
    content = f.read()

# 1. Update the CSS
css_addition = """
        .registration-card {
            background: #fff;
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            margin-bottom: 20px;
            padding: 18px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .registration-card-header {
            background: #eaf3ff;
            border-left: 4px solid #2b6cb0;
            padding: 10px 14px;
            font-weight: 600;
            color: #1e3a5f;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 15px;
        }
        .registration-card .form-label {
            font-size: 13px;
            color: #334155;
            text-transform: none;
            font-weight: 600;
        }
        .registration-card .form-control, .registration-card .form-select {
            height: 42px;
            border-radius: 8px;
            border: 1px solid #cfd8e3;
        }
        .registration-card textarea.form-control {
            min-height: 100px;
        }
        .table-gov {
            width: 100%;
            border-collapse: collapse;
        }
        .table-gov th {
            background: #f1f5f9;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            padding: 10px;
            border: 1px solid #e2e8f0;
        }
        .table-gov td {
            padding: 10px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .table-gov input.form-control, .table-gov select.form-select {
            height: 36px;
            font-size: 13px;
            border-radius: 4px;
        }
        .btn-add-row {
            background: #fff;
            color: #2b6cb0;
            border: 1px solid #2b6cb0;
            padding: 4px 12px;
            font-size: 12px;
            border-radius: 4px;
            font-weight: 600;
            float: right;
        }
        .btn-remove-row {
            color: #e53e3e;
            background: none;
            border: none;
            cursor: pointer;
        }
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 30px;
        }
        .btn-prev {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #475569;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
        }
        .btn-draft {
            background: #f8fafc;
            border: 1px solid #94a3b8;
            color: #334155;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
        }
        .btn-submit-reg {
            background: #2b6cb0;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
        }
        .btn-submit-reg:hover { background: #1e4e8c; color:#fff; }
"""
if ".registration-card {" not in content:
    content = content.replace("</style>", css_addition + "\n    </style>")

# 2. Extract tab content
tab_start = content.find('<div class="tab-pane fade" id="registrationDetails"')
tab_end = content.find('</form>', tab_start)

# We want to replace everything from tab_start to right before </div>\n        </div>\n    </form>
# The structure is:
# <div class="tab-pane fade" id="registrationDetails" role="tabpanel">
# ...
# </div> <!-- ends registrationDetails -->
# </div> <!-- ends tab-content -->

tab_content_end = content.rfind('</div>', tab_start, content.find('</div>\n    </form>'))
tab_content_end = content.find('</div>', tab_content_end - 10) # rough, let's use regex

match = re.search(r'(<div class="tab-pane fade" id="registrationDetails" role="tabpanel">)(.*?)(</div>\s*</div>\s*</form>)', content, re.DOTALL)
if match:
    # 3. New HTML
    new_html = """
            <div class="tab-pane fade" id="registrationDetails" role="tabpanel">
                
                <div class="registration-card">
                    <div class="registration-card-header">1. Work Awarding Dept</div>
                    <div class="row">
                        <div class="col-md-12">
                            <select class="form-select" name="work_awarding_department" required <?= $is_readonly ? 'disabled' : '' ?>>
                                <option value="">-- Select Department --</option>
                                <?php
                                $depts = db_fetch_all($conn, "SELECT dept_name FROM master_departments WHERE status='active' ORDER BY dept_name ASC");
                                foreach($depts as $d): ?>
                                    <option value="<?= htmlspecialchars($d['dept_name']) ?>" <?= ($c['work_awarding_department'] ?? '') === $d['dept_name'] ? 'selected' : '' ?>><?= htmlspecialchars($d['dept_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="registration-card">
                    <div class="registration-card-header">2. Whether Registered under EPF</div>
                    <div class="row">
                        <div class="col-md-6">
                            <select class="form-select" name="epf_registered" id="epf_registered" onchange="toggleEPF()" required <?= $is_readonly ? 'disabled' : '' ?>>
                                <option value="YES" <?= ($c['epf_registered'] ?? '') === 'YES' ? 'selected' : '' ?>>YES</option>
                                <option value="NO" <?= ($c['epf_registered'] ?? '') === 'NO' ? 'selected' : '' ?>>NO</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="registration-card" id="section_epf_details">
                    <div class="registration-card-header">3. Establishment EPF ID + EPF Account Number</div>
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label required">Establishment EPF ID</label>
                            <input type="text" class="form-control" name="epf_code" id="epf_code" value="<?= htmlspecialchars($c['epf_code'] ?? '') ?>" <?= $is_readonly ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">EPF Account Number</label>
                            <input type="text" class="form-control" name="epf_account_no" id="epf_account_no" value="<?= htmlspecialchars($c['epf_account_no'] ?? '') ?>" <?= $is_readonly ? 'readonly' : '' ?>>
                        </div>
                    </div>
                </div>

                <div class="registration-card">
                    <div class="registration-card-header">4. Whether Registered under ESI</div>
                    <div class="row align-items-end">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <select class="form-select" name="esi_registered" id="esi_registered" onchange="toggleESI()" required <?= $is_readonly ? 'disabled' : '' ?>>
                                <option value="YES" <?= ($c['esi_registered'] ?? '') === 'YES' ? 'selected' : '' ?>>YES</option>
                                <option value="NO" <?= ($c['esi_registered'] ?? '') === 'NO' ? 'selected' : '' ?>>NO</option>
                            </select>
                            <div id="esi_warning" class="text-danger mt-2 fw-bold" style="font-size:12px; display:none;">
                                <i class="fas fa-exclamation-triangle"></i> Either ESI or EC Policy mandatory
                            </div>
                        </div>
                        <div class="col-md-6" id="section_esi_code">
                            <label class="form-label required">ESI Establishment Code</label>
                            <input type="text" class="form-control" name="esi_code" id="esi_code" value="<?= htmlspecialchars($c['esi_code'] ?? '') ?>" <?= $is_readonly ? 'readonly' : '' ?>>
                        </div>
                    </div>
                </div>

                <div class="registration-card">
                    <div class="registration-card-header">5. Wage Declaration by Contractor</div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Wage Category</label>
                            <select class="form-select" name="wage_category" required <?= $is_readonly ? 'disabled' : '' ?>>
                                <option value="">-- Select Wage Category --</option>
                                <option value="Skilled" <?= ($c['wage_category'] ?? '') === 'Skilled' ? 'selected' : '' ?>>Skilled</option>
                                <option value="Semiskilled" <?= ($c['wage_category'] ?? '') === 'Semiskilled' ? 'selected' : '' ?>>Semiskilled</option>
                                <option value="Unskilled" <?= ($c['wage_category'] ?? '') === 'Unskilled' ? 'selected' : '' ?>>Unskilled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Wage Declaration Details (Optional)</label>
                            <input type="text" class="form-control" name="wage_declaration" value="<?= htmlspecialchars($c['wage_declaration'] ?? '') ?>" <?= $is_readonly ? 'readonly' : '' ?>>
                        </div>
                    </div>
                </div>

                <div class="registration-card">
                    <div class="registration-card-header">6. Employee Compensation Policy
                        <?php if (!$is_readonly): ?>
                            <select class="form-select form-select-sm d-inline-block w-auto ms-3" name="ecp_covered" id="ecp_covered" onchange="toggleECP()">
                                <option value="YES" <?= ($c['ecp_covered'] ?? 'YES') === 'YES' ? 'selected' : '' ?>>YES</option>
                                <option value="NO" <?= ($c['ecp_covered'] ?? '') === 'NO' ? 'selected' : '' ?>>NO</option>
                            </select>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-2"><?= htmlspecialchars($c['ecp_covered'] ?? 'YES') ?></span>
                            <input type="hidden" name="ecp_covered" id="ecp_covered" value="<?= htmlspecialchars($c['ecp_covered'] ?? 'YES') ?>">
                        <?php endif; ?>
                    </div>
                    
                    <div id="section_ecp_table">
                        <?php if (!$is_readonly): ?>
                        <button type="button" class="btn-add-row mb-2" onclick="addEcpRow()">+ Add Row</button>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table-gov" id="ecpTable">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">S.No</th>
                                        <th>EC Policy Number</th>
                                        <th>Valid From</th>
                                        <th>Valid To</th>
                                        <th>Insurance Company</th>
                                        <?php if (!$is_readonly): ?><th style="width: 60px;">Action</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody id="ecpTableBody">
                                    <!-- Dynamic rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="registration-card" id="section_reason">
                    <div class="registration-card-header">7. Reason</div>
                    <label class="form-label required">Reason for Non-Registration under ESI/EC Policy</label>
                    <textarea class="form-control" name="epf_esi_exemption_reason" id="epf_esi_exemption_reason" <?= $is_readonly ? 'readonly' : '' ?>><?= htmlspecialchars($c['epf_esi_exemption_reason'] ?? '') ?></textarea>
                </div>

                <div class="registration-card">
                    <div class="registration-card-header">8. Number of Workmen Proposed</div>
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <input type="number" class="form-control" name="workers_proposed_to_be_engaged" value="<?= htmlspecialchars($c['workers_proposed_to_be_engaged'] ?? '') ?>" required <?= $is_readonly ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6 d-flex align-items-center gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="worker_categories[]" value="Skilled" id="cat_skilled" <?= in_array('Skilled', $worker_cats) ? 'checked' : '' ?> <?= $is_readonly ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="cat_skilled">Skilled</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="worker_categories[]" value="Semiskilled" id="cat_semiskilled" <?= in_array('Semiskilled', $worker_cats) ? 'checked' : '' ?> <?= $is_readonly ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="cat_semiskilled">Semiskilled</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="worker_categories[]" value="Unskilled" id="cat_unskilled" <?= in_array('Unskilled', $worker_cats) ? 'checked' : '' ?> <?= $is_readonly ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="cat_unskilled">Unskilled</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="registration-card">
                    <div class="registration-card-header">9. Labour License Details</div>
                    <div class="table-responsive">
                        <?php if (!$is_readonly): ?>
                        <button type="button" class="btn-add-row mb-2" onclick="addLicenseRow()">+ Add Row</button>
                        <?php endif; ?>
                        <table class="table-gov" id="licenseTable">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">S.No</th>
                                    <th>Labour ID</th>
                                    <th>Validity</th>
                                    <th>Issued Date</th>
                                    <th>Expiry Date</th>
                                    <th>License Upload</th>
                                    <?php if (!$is_readonly): ?><th style="width: 60px;">Action</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="licenseTableBody">
                                <!-- Dynamic rows -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="registration-card">
                    <div class="registration-card-header">10. Labour License Application Number</div>
                    <input type="text" class="form-control" name="labour_license_appl_no" value="<?= htmlspecialchars($c['labour_license_appl_no'] ?? '') ?>" <?= $is_readonly ? 'readonly' : '' ?>>
                </div>

                <div class="registration-card">
                    <div class="registration-card-header">11. Labour Identification Number</div>
                    <input type="text" class="form-control" name="labour_identification_no" value="<?= htmlspecialchars($c['labour_identification_no'] ?? '') ?>" <?= $is_readonly ? 'readonly' : '' ?>>
                </div>

                <div class="registration-card">
                    <div class="registration-card-header">12. Name of Contact Person</div>
                    <input type="text" class="form-control" name="contact_person" value="<?= htmlspecialchars($c['contact_person'] ?? '') ?>" required <?= $is_readonly ? 'readonly' : '' ?>>
                </div>

                <div class="registration-card">
                    <div class="registration-card-header">13. Mobile Number + Alternate Mobile Number</div>
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label class="form-label required">Mobile Number</label>
                            <input type="text" class="form-control" name="mobile" value="<?= htmlspecialchars($c['mobile'] ?? '') ?>" required <?= $is_readonly ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alternate Mobile Number</label>
                            <input type="text" class="form-control" name="vendor_mob2" value="<?= htmlspecialchars($c['vendor_mob2'] ?? '') ?>" <?= $is_readonly ? 'readonly' : '' ?>>
                        </div>
                    </div>
                </div>

                <div class="registration-card">
                    <div class="registration-card-header">14. Remarks</div>
                    <textarea class="form-control" name="remarks" <?= $is_readonly ? 'readonly' : '' ?>><?= htmlspecialchars($c['remarks'] ?? '') ?></textarea>
                </div>

                <?php if (!$is_readonly): ?>
                <div class="action-buttons">
                    <button type="button" class="btn-prev" onclick="showTab('basicDetails')">Previous</button>
                    <button type="button" class="btn-draft" onclick="saveDraft()">Save Draft</button>
                    <button type="submit" class="btn-submit-reg" id="submitBtn">Submit Registration</button>
                </div>
                <?php endif; ?>
            </div>
"""
    
    content = content[:match.start(1)] + new_html + match.group(3)

# 4. Modify JavaScript part
js_start = content.find('<script>')
if js_start != -1:
    # First, let's inject variables
    variables = """
    const isReadonly = <?= json_encode($is_readonly) ?>;
    const ecpDataJson = <?= !empty($c['ecp_details_json']) ? $c['ecp_details_json'] : '[]' ?>;
    const licenseDataJson = <?= !empty($c['license_details_json']) ? $c['license_details_json'] : '[]' ?>;
"""
    content = content.replace('<script>', '<script>\n' + variables)
    
    # We will replace some functions: toggleEPF, toggleESI, toggleECP, etc.
    # It's easier to append the new JS logic and let it override or just rewrite the script tag content carefully.
    
    script_content_match = re.search(r'(<script>\s*const isReadonly =.*?</script>)', content, re.DOTALL)
    # Actually, I'll rewrite the entire script tag except for fetchSAPData which is large and unchanged.
    pass

with open("c:/xampp/htdocs/clms/pages/contractor/annexure-2a.php", "w", encoding="utf-8") as f:
    f.write(content)

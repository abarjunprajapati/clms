<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Annexure 2A – Contractor Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../css/annexure2a.css" />
</head>
<body>
    <div class="container">
        <h1>Annexure 2A – Contractor Registration</h1>
        <form id="annexure2aForm" action="../api/save_annexure2a.php" method="POST" enctype="multipart/form-data">
            <!-- Hidden vendor identifiers – these could be populated server‑side -->
            <input type="hidden" name="vendor_code" value="<?= htmlspecialchars($vendor_code ?? '') ?>" />
            <input type="hidden" name="vendor_name" value="<?= htmlspecialchars($vendor_name ?? '') ?>" />
            <input type="hidden" name="action" id="formAction" value="save" />

            <!-- SECTION 1 – Work Awarding Dept -->
            <section id="section1" class="form-section">
                <h2>1. Work Awarding Department</h2>
                <label for="work_awarding_department">Department<span class="required">*</span></label>
                <select id="work_awarding_department" name="work_awarding_department" required>
                    <option value="">Select Department</option>
                    <!-- Options will be filled dynamically from master table via JS -->
                </select>
            </section>

            <!-- SECTION 2 – EPF Registration -->
            <section id="section2" class="form-section">
                <h2>2. Registered under EPF?</h2>
                <div class="radio-group">
                    <label><input type="radio" name="epf_registered" value="YES" required> Yes</label>
                    <label><input type="radio" name="epf_registered" value="NO" checked> No</label>
                </div>
                <div id="epfDetails" class="nested-section hidden">
                    <label for="epf_code">Establishment EPF ID<span class="required">*</span></label>
                    <input type="text" id="epf_code" name="epf_code" pattern="[A-Za-z0-9]+" />
                    <label for="epf_account_no">EPF Account Number<span class="required">*</span></label>
                    <input type="text" id="epf_account_no" name="epf_account_no" pattern="[A-Za-z0-9]+" />
                </div>
            </section>

            <!-- SECTION 4 – ESI Registration (named Section 4 in spec) -->
            <section id="section4" class="form-section">
                <h2>4. Registered under ESI?</h2>
                <div class="radio-group">
                    <label><input type="radio" name="esi_registered" value="YES" required> Yes</label>
                    <label><input type="radio" name="esi_registered" value="NO" checked> No</label>
                </div>
                <div id="esiDetails" class="nested-section hidden">
                    <label for="esi_code">ESI Registration Number<span class="required">*</span></label>
                    <input type="text" id="esi_code" name="esi_code" pattern="[A-Za-z0-9]+" />
                </div>
                <div id="esiWarning" class="warning hidden">
                    <p class="warning-text">Either ESI or EC Policy is mandatory.</p>
                </div>
            </section>

            <!-- SECTION 5 – Wage Declaration -->
            <section id="section5" class="form-section">
                <h2>5. Wage Declaration by Contractor</h2>
                <textarea name="wage_declaration" rows="4" placeholder="Enter wage declaration..." required></textarea>
            </section>

            <!-- SECTION 6 – Employee Compensation Policy (EC) -->
            <section id="section6" class="form-section">
                <h2>6A. Covered under EC Policy?</h2>
                <div class="radio-group">
                    <label><input type="radio" name="ecp_covered" value="YES" required> Yes</label>
                    <label><input type="radio" name="ecp_covered" value="NO" checked> No</label>
                </div>
                <div id="ecpTableSection" class="nested-section hidden">
                    <table id="ecpTable" class="dynamic-table">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>EC Policy Number</th>
                                <th>Valid From</th>
                                <th>Valid To</th>
                                <th>Insurance Company</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Rows will be injected by JS -->
                        </tbody>
                    </table>
                    <button type="button" id="addEcpRow" class="btn-primary">Add EC Policy</button>
                </div>
                <div id="ecpReasonSection" class="nested-section hidden">
                    <label for="epf_esi_exemption_reason">Reason (why no EC Policy)</label>
                    <textarea name="epf_esi_exemption_reason" id="epf_esi_exemption_reason" rows="3"></textarea>
                </div>
            </section>

            <!-- SECTION 8 – Number of Workmen Proposed -->
            <section id="section8" class="form-section">
                <h2>8. Number of Workmen Proposed</h2>
                <input type="number" name="workers_proposed_to_be_engaged" min="0" required />
                <div class="checkbox-group">
                    <label><input type="checkbox" name="worker_categories[]" value="Skilled"> Skilled</label>
                    <label><input type="checkbox" name="worker_categories[]" value="Semi Skilled"> Semi Skilled</label>
                    <label><input type="checkbox" name="worker_categories[]" value="Unskilled"> Unskilled</label>
                    <label><input type="checkbox" name="worker_categories[]" value="Others"> Others</label>
                </div>
            </section>

            <!-- SECTION 9 – Labour License Details -->
            <section id="section9" class="form-section">
                <h2>9. Labour License Details</h2>
                <table id="licenseTable" class="dynamic-table">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>License No</th>
                            <th>Validity</th>
                            <th>Issued Date</th>
                            <th>Expiry Date</th>
                            <th>Issued By</th>
                            <th>Upload PDF</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows injected by JS -->
                    </tbody>
                </table>
                <button type="button" id="addLicenseRow" class="btn-primary">Add License</button>
            </section>

            <!-- SECTION 10 – Labour License Application Number -->
            <section id="section10" class="form-section">
                <h2>10. Labour License Application Number</h2>
                <input type="text" name="labour_license_appl_no" />
            </section>

            <!-- SECTION 11 – Labour Identification Number -->
            <section id="section11" class="form-section">
                <h2>11. Labour Identification Number</h2>
                <input type="text" name="labour_identification_no" />
            </section>

            <!-- SECTION 12 – Contact Person -->
            <section id="section12" class="form-section">
                <h2>12. Contact Person</h2>
                <input type="text" name="contact_person" required />
            </section>

            <!-- SECTION 13 – Mobile Numbers -->
            <section id="section13" class="form-section two-column">
                <h2>13. Mobile Numbers</h2>
                <div>
                    <label for="mobile">Mobile No<span class="required">*</span></label>
                    <input type="text" id="mobile" name="mobile" pattern="[0-9]{10}" required />
                </div>
                <div>
                    <label for="vendor_mob2">Alternate Mobile No</label>
                    <input type="text" id="vendor_mob2" name="vendor_mob2" pattern="[0-9]{10}" />
                </div>
            </section>

            <!-- SECTION 14 – Remarks -->
            <section id="section14" class="form-section">
                <h2>14. Remarks</h2>
                <textarea name="remarks" rows="3"></textarea>
            </section>

            <div class="form-actions">
                <button type="button" id="saveDraft" class="btn-secondary">Save Draft</button>
                <button type="button" id="submitForm" class="btn-primary">Submit</button>
            </div>
        </form>
    </div>
    <script src="../js/annexure2a.js"></script>
</body>
</html>

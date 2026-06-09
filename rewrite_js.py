import re

with open("c:/xampp/htdocs/clms/pages/contractor/annexure-2a.php", "r", encoding="utf-8") as f:
    content = f.read()

# Replace the script tag completely
js_start = content.find('<script>')
js_end = content.rfind('</script>') + len('</script>')

if js_start != -1 and js_end != -1:
    old_script = content[js_start:js_end]
    
    new_script = """<script>
    const isReadonly = <?= json_encode($is_readonly) ?>;
    const ecpDataJson = <?= !empty($c['ecp_details_json']) ? $c['ecp_details_json'] : '[]' ?>;
    const licenseDataJson = <?= !empty($c['license_details_json']) ? $c['license_details_json'] : '[]' ?>;
    const licenceThreshold = <?= $licence_threshold ?>;

    function showTab(id) {
        const tabEl = document.querySelector(`a[href="#${id}"]`);
        const tab = new bootstrap.Tab(tabEl);
        tab.show();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function toggleEPF() {
        const val = document.getElementById('epf_registered').value;
        const section = document.getElementById('section_epf_details');
        const code = document.getElementById('epf_code');
        const acc = document.getElementById('epf_account_no');

        if (val === 'YES') {
            section.style.display = 'block';
            code.required = true;
            acc.required = true;
        } else {
            section.style.display = 'none';
            code.required = false;
            acc.required = false;
            code.value = '';
            acc.value = '';
        }
    }

    function toggleESI() {
        const esi = document.getElementById('esi_registered').value;
        const ecp = document.getElementById('ecp_covered')?.value || 'YES';
        const section = document.getElementById('section_esi_code');
        const code = document.getElementById('esi_code');
        const warning = document.getElementById('esi_warning');

        if (esi === 'YES') {
            section.style.display = 'block';
            code.required = true;
            warning.style.display = 'none';
        } else {
            section.style.display = 'none';
            code.required = false;
            code.value = '';
            if (ecp === 'NO') {
                warning.style.display = 'block';
            } else {
                warning.style.display = 'none';
            }
        }
    }

    function toggleECP() {
        const ecp = document.getElementById('ecp_covered')?.value || 'YES';
        const esi = document.getElementById('esi_registered').value;
        const warning = document.getElementById('esi_warning');
        const tableSec = document.getElementById('section_ecp_table');
        const reasonSec = document.getElementById('section_reason');
        const reasonInput = document.getElementById('epf_esi_exemption_reason');

        if (ecp === 'YES') {
            tableSec.style.display = 'block';
            reasonSec.style.display = 'none';
            reasonInput.required = false;
            if (esi === 'NO') warning.style.display = 'none';
        } else {
            tableSec.style.display = 'none';
            reasonSec.style.display = 'block';
            reasonInput.required = true;
            if (esi === 'NO') warning.style.display = 'block';
        }
    }

    function updateSlNos(tbodyId) {
        const rows = document.querySelectorAll(`#${tbodyId} tr`);
        rows.forEach((row, index) => {
            const slNo = row.querySelector('.sl-no');
            if (slNo) slNo.innerText = index + 1;
        });
    }

    function addEcpRow(data = null) {
        const tbody = document.getElementById('ecpTableBody');
        const tr = document.createElement('tr');
        
        const no = data ? data.ecp_number : '';
        const vFrom = data ? data.ecp_valid_from : '';
        const vTo = data ? data.ecp_valid_to : '';
        const ins = data ? data.insurance_company : '';

        tr.innerHTML = `
            <td class="sl-no text-center fw-bold text-muted"></td>
            <td><input type="text" class="form-control" name="ecp_number[]" value="${no}" required ${isReadonly ? 'readonly' : ''}></td>
            <td><input type="date" class="form-control ecp-from" name="ecp_valid_from[]" value="${vFrom}" required ${isReadonly ? 'readonly' : ''}></td>
            <td>
                <input type="date" class="form-control ecp-to" name="ecp_valid_to[]" value="${vTo}" required ${isReadonly ? 'readonly' : ''}>
                <div class="text-danger ecp-date-error mt-1" style="font-size:10px; display:none;">Invalid</div>
            </td>
            <td><input type="text" class="form-control" name="ecp_insurance_company[]" value="${ins}" required ${isReadonly ? 'readonly' : ''}></td>
            ${isReadonly ? '' : '<td class="text-center"><button type="button" class="btn-remove-row" onclick="deleteEcpRow(this)"><i class="fas fa-trash-alt"></i></button></td>'}
        `;
        tbody.appendChild(tr);
        updateSlNos('ecpTableBody');
    }

    function deleteEcpRow(btn) {
        btn.closest('tr').remove();
        updateSlNos('ecpTableBody');
    }

    function addLicenseRow(data = null) {
        const tbody = document.getElementById('licenseTableBody');
        const tr = document.createElement('tr');
        
        const no = data ? data.license_no : '';
        const val = data ? (data.validity || data.license_validity || '') : '';
        const iDate = data ? data.issued_date : '';
        const eDate = data ? data.expiry_date : '';
        const fPath = data ? data.file_path : '';
        
        let fileHtml = `<input type="file" class="form-control" name="license_file[]" accept=".pdf" ${fPath ? '' : 'required'} ${isReadonly ? 'disabled' : ''}>`;
        if (fPath) {
            fileHtml += `<a href="../../uploads/contractors/${fPath}" target="_blank" class="d-block mt-1 text-success fw-bold" style="font-size:11px;"><i class="fas fa-check-circle"></i> View Uploaded</a>
            <input type="hidden" name="existing_license_file[]" value="${fPath}">`;
        }

        tr.innerHTML = `
            <td class="sl-no text-center fw-bold text-muted"></td>
            <td><input type="text" class="form-control" name="license_no[]" value="${no}" required ${isReadonly ? 'readonly' : ''}></td>
            <td><input type="text" class="form-control" name="license_validity[]" value="${val}" required ${isReadonly ? 'readonly' : ''}></td>
            <td><input type="date" class="form-control" name="issued_date[]" value="${iDate}" required ${isReadonly ? 'readonly' : ''}></td>
            <td><input type="date" class="form-control" name="expiry_date[]" value="${eDate}" required ${isReadonly ? 'readonly' : ''}></td>
            <td>${fileHtml}</td>
            ${isReadonly ? '' : '<td class="text-center"><button type="button" class="btn-remove-row" onclick="deleteLicenseRow(this)"><i class="fas fa-trash-alt"></i></button></td>'}
        `;
        tbody.appendChild(tr);
        updateSlNos('licenseTableBody');
    }

    function deleteLicenseRow(btn) {
        btn.closest('tr').remove();
        updateSlNos('licenseTableBody');
    }

    function validateAllDates() {
        let valid = true;
        document.querySelectorAll('#ecpTableBody tr').forEach(tr => {
            const f = tr.querySelector('.ecp-from').value;
            const t = tr.querySelector('.ecp-to').value;
            const err = tr.querySelector('.ecp-date-error');
            if (f && t && new Date(f) > new Date(t)) {
                err.style.display = 'block';
                valid = false;
            } else if (err) {
                err.style.display = 'none';
            }
        });
        return valid;
    }

    async function fetchSAPData() {
        const code = document.querySelector('[name="vendor_code"]').value;
        if (!code) return;
        
        document.getElementById('po-loading').style.display = 'block';
        
        try {
            // Fetch POs
            const poResp = await fetch(`../../api/contractor/get_vendor_pos.php?vendor_code=${code}`);
            const poData = await poResp.json();
            const poBody = document.getElementById('poTableBody');
            if (poData.status === 'success' && poData.data.length > 0) {
                poBody.innerHTML = poData.data.map(p => `
                    <tr>
                        <td class="ps-4"><input type="checkbox" class="po-check form-check-input" value="${p.po_number}"></td>
                        <td><span class="fw-bold text-dark">${p.po_number}</span></td>
                        <td><span class="badge bg-light text-dark border">${p.po_type}</span></td>
                        <td>${p.purchasing_group}</td>
                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${p.header_text}</td>
                        <td>${p.currency}</td>
                        <td class="fw-bold">${p.total_value}</td>
                        <td>${p.document_date}</td>
                        <td><span class="badge ${p.release_status==='R'?'bg-success':'bg-warning'}">${p.release_status==='R'?'Released':'Pending'}</span></td>
                    </tr>
                `).join('');
            }

            // Fetch PWOs
            const pwoResp = await fetch(`../../api/contractor/get_vendor_pwos.php?vendor_code=${code}`);
            const pwoData = await pwoResp.json();
            const pwoBody = document.getElementById('pwoTableBody');
            if(pwoData.status === 'success' && pwoData.data.length > 0) {
                pwoBody.innerHTML = pwoData.data.map(p => `
                    <tr>
                        <td class="ps-3"><input type="checkbox" class="pwo-check form-check-input" value="${p.pwo_number}"></td>
                        <td>${p.pwo_number}</td>
                        <td>${p.vessel}</td>
                        <td>${p.work_completion_date}</td>
                    </tr>
                `).join('');
            }

            // Fetch Sales Orders
            const soResp = await fetch(`../../api/contractor/get_vendor_sales.php?vendor_code=${code}`);
            const soData = await soResp.json();
            const soBody = document.getElementById('soTableBody');
            if(soData.status === 'success' && soData.data.length > 0) {
                soBody.innerHTML = soData.data.map(s => `
                    <tr>
                        <td class="ps-3"><input type="checkbox" class="so-check form-check-input" value="${s.sale_order_no}"></td>
                        <td>${s.sale_order_no}</td>
                        <td class="fw-bold">${s.amount}</td>
                        <td>${s.currency}</td>
                    </tr>
                `).join('');
            }
        } catch (e) {
            console.error("SAP Fetch Error", e);
        } finally {
            document.getElementById('po-loading').style.display = 'none';
        }
    }

    function collectData() {
        const pos = Array.from(document.querySelectorAll('.po-check:checked')).map(cb => cb.value);
        const pwos = Array.from(document.querySelectorAll('.pwo-check:checked')).map(cb => cb.value);
        const sos = Array.from(document.querySelectorAll('.so-check:checked')).map(cb => cb.value);
        document.getElementById('selected_pos').value = JSON.stringify(pos);
        document.getElementById('selected_pwos').value = JSON.stringify(pwos);
        document.getElementById('selected_sales').value = JSON.stringify(sos);
    }

    async function saveDraft() {
        collectData();
        const form = document.getElementById('annexure2AForm');
        const formData = new FormData(form);
        formData.append('action', 'draft');
        try {
            const resp = await fetch('../../api/save_annexure2a.php', { method: 'POST', body: formData });
            const res = await resp.json();
            alert(res.message || 'Draft saved successfully.');
        } catch (err) { alert('Error saving draft'); }
    }

    document.getElementById('annexure2AForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const form = e.target;
        const isDateValid = validateAllDates();

        if (!form.checkValidity() || !isDateValid) {
            e.stopPropagation();
            form.classList.add('was-validated');
            if (form.querySelector('#registrationDetails .form-control:invalid, #registrationDetails .form-select:invalid') || !isDateValid) {
                showTab('registrationDetails');
            } else {
                showTab('basicDetails');
            }
            return;
        }

        collectData();
        const btn = document.getElementById('submitBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'PROCESSING...';
        
        const formData = new FormData(form);
        formData.append('action', 'submit');

        try {
            const resp = await fetch('../../api/save_annexure2a.php', { method: 'POST', body: formData });
            const res = await resp.json();
            if (res.success) {
                alert('Registration submitted successfully! Your application is now under Welfare review.');
                window.location.href = 'dashboard.php';
            } else {
                alert(res.message || 'Error submitting registration');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (err) {
            alert('Network error. Please try again.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    window.addEventListener('load', () => {
        fetchSAPData();
        toggleEPF();
        toggleESI();
        toggleECP();
        
        // Init dynamic rows
        if (ecpDataJson && ecpDataJson.length > 0) {
            ecpDataJson.forEach(d => addEcpRow(d));
        } else if (!isReadonly) {
            addEcpRow();
        }

        if (licenseDataJson && licenseDataJson.length > 0) {
            licenseDataJson.forEach(d => addLicenseRow(d));
        } else if (!isReadonly) {
            addLicenseRow();
        }
    });

    // Select all logic
    document.getElementById('selectAllPO')?.addEventListener('change', (e) => {
        document.querySelectorAll('.po-check').forEach(cb => cb.checked = e.target.checked);
    });
    document.getElementById('selectAllPWO')?.addEventListener('change', (e) => {
        document.querySelectorAll('.pwo-check').forEach(cb => cb.checked = e.target.checked);
    });
    document.getElementById('selectAllSO')?.addEventListener('change', (e) => {
        document.querySelectorAll('.so-check').forEach(cb => cb.checked = e.target.checked);
    });
</script>"""
    
    content = content[:js_start] + new_script + content[js_end:]
    
    with open("c:/xampp/htdocs/clms/pages/contractor/annexure-2a.php", "w", encoding="utf-8") as f:
        f.write(content)

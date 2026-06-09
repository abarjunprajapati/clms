// annexure2a.js – Handles UI activation, dynamic rows, and form actions

document.addEventListener('DOMContentLoaded', () => {
  // ---- Utility Functions ----
  const qs = (s) => document.querySelector(s);
  const qsa = (s) => document.querySelectorAll(s);

  // ---- Populate Work Awarding Department (placeholder – replace with real API) ----
  fetch('../api/get_departments.php')
    .then((r) => r.json())
    .then((data) => {
      const select = qs('#work_awarding_department');
      if (Array.isArray(data)) {
        data.forEach((dept) => {
          const opt = document.createElement('option');
          opt.value = dept.id || dept.code || dept;
          opt.textContent = dept.name || dept;
          select.appendChild(opt);
        });
      }
    })
    .catch(() => {
      // fallback static options if API fails
      const staticOpts = ['Civil Works', 'Electrical', 'Mechanical', 'IT Services'];
      const select = qs('#work_awarding_department');
      staticOpts.forEach((name) => {
        const opt = document.createElement('option');
        opt.value = name.toLowerCase().replace(/\s+/g, '_');
        opt.textContent = name;
        select.appendChild(opt);
      });
    });

  // ---- Section Visibility Logic ----
  const toggleSection = (radioName, targetId, showWhenValue = 'YES') => {
    const radios = qsa(`input[name="${radioName}"]`);
    radios.forEach((r) => {
      r.addEventListener('change', () => {
        const target = qs(`#${targetId}`);
        if (r.checked && r.value === showWhenValue) {
          target.classList.remove('hidden');
        } else if (r.checked && r.value !== showWhenValue) {
          // clear inputs inside hidden area
          target.querySelectorAll('input, textarea, select').forEach((el) => (el.value = ''));
          target.classList.add('hidden');
        }
      });
    });
  };

  // EPF Section (2)
  toggleSection('epf_registered', 'epfDetails');
  // ESI Section (4) – also show warning when both NO
  const esiRadios = qsa('input[name="esi_registered"]');
  const esiDetails = qs('#esiDetails');
  const esiWarning = qs('#esiWarning');
  const ecpRadios = qsa('input[name="ecp_covered"]');

  esiRadios.forEach((r) => {
    r.addEventListener('change', () => {
      if (r.checked && r.value === 'YES') {
        esiDetails.classList.remove('hidden');
        esiWarning.classList.add('hidden');
      } else if (r.checked && r.value === 'NO') {
        esiDetails.classList.add('hidden');
        // if EC also NO, show warning
        const ecpVal = document.querySelector('input[name="ecp_covered"]:checked')?.value;
        if (ecpVal === 'NO') {
          esiWarning.classList.remove('hidden');
        } else {
          esiWarning.classList.add('hidden');
        }
        // clear ESI fields
        esiDetails.querySelectorAll('input, textarea, select').forEach((el) => (el.value = ''));
      }
    });
  });

  // EC Policy Section (6A)
  const ecpTableSection = qs('#ecpTableSection');
  const ecpReasonSection = qs('#ecpReasonSection');
  ecpRadios.forEach((r) => {
    r.addEventListener('change', () => {
      if (r.checked && r.value === 'YES') {
        ecpTableSection.classList.remove('hidden');
        ecpReasonSection.classList.add('hidden');
        // clear reason textarea
        qs('#epf_esi_exemption_reason').value = '';
      } else if (r.checked && r.value === 'NO') {
        ecpTableSection.classList.add('hidden');
        ecpReasonSection.classList.remove('hidden');
        // clear any existing EC rows
        qs('#ecpTable tbody').innerHTML = '';
      }
    });
  });

  // ---- Dynamic Row Helpers ----
  const createRow = (cols) => {
    const tr = document.createElement('tr');
    cols.forEach((col) => tr.appendChild(col));
    return tr;
  };

  // EC Policy Table
  const ecpTableBody = qs('#ecpTable tbody');
  const addEcpRowBtn = qs('#addEcpRow');
  const addEcpRow = () => {
    const rowCount = ecpTableBody.children.length + 1;
    const cells = [];
    // S.No (read‑only)
    const sno = document.createElement('td');
    sno.textContent = rowCount;
    cells.push(sno);
    // EC Policy Number
    const num = document.createElement('td');
    const numIn = document.createElement('input');
    numIn.type = 'text';
    numIn.name = 'ecp_number[]';
    numIn.required = true;
    num.appendChild(numIn);
    cells.push(num);
    // Valid From
    const from = document.createElement('td');
    const fromIn = document.createElement('input');
    fromIn.type = 'date';
    fromIn.name = 'ecp_valid_from[]';
    fromIn.required = true;
    from.appendChild(fromIn);
    cells.push(from);
    // Valid To
    const to = document.createElement('td');
    const toIn = document.createElement('input');
    toIn.type = 'date';
    toIn.name = 'ecp_valid_to[]';
    toIn.required = true;
    to.appendChild(toIn);
    cells.push(to);
    // Insurance Company
    const insurer = document.createElement('td');
    const insurerIn = document.createElement('input');
    insurerIn.type = 'text';
    insurerIn.name = 'ecp_insurance_company[]';
    insurer.appendChild(insurerIn);
    cells.push(insurer);
    // Actions (remove button)
    const act = document.createElement('td');
    const rmBtn = document.createElement('button');
    rmBtn.type = 'button';
    rmBtn.textContent = 'Remove';
    rmBtn.className = 'btn-secondary';
    rmBtn.addEventListener('click', () => {
      ecpTableBody.removeChild(tr);
      // re‑index remaining rows
      Array.from(ecpTableBody.children).forEach((r, i) => (r.children[0].textContent = i + 1));
    });
    act.appendChild(rmBtn);
    cells.push(act);

    const tr = createRow(cells);
    ecpTableBody.appendChild(tr);
  };
  addEcpRowBtn.addEventListener('click', addEcpRow);

  // Licence Table
  const licenseTableBody = qs('#licenseTable tbody');
  const addLicenseBtn = qs('#addLicenseRow');
  const addLicenseRow = () => {
    const rowCount = licenseTableBody.children.length + 1;
    const cells = [];
    // S.No
    const sno = document.createElement('td');
    sno.textContent = rowCount;
    cells.push(sno);
    // License No
    const licNo = document.createElement('td');
    const licIn = document.createElement('input');
    licIn.type = 'text';
    licIn.name = 'license_no[]';
    licIn.required = true;
    licNo.appendChild(licIn);
    cells.push(licNo);
    // Validity (free text)
    const validity = document.createElement('td');
    const valIn = document.createElement('input');
    valIn.type = 'text';
    valIn.name = 'license_validity[]';
    valIn.required = true;
    validity.appendChild(valIn);
    cells.push(validity);
    // Issued Date
    const issued = document.createElement('td');
    const issuedIn = document.createElement('input');
    issuedIn.type = 'date';
    issuedIn.name = 'issued_date[]';
    issuedIn.required = true;
    issued.appendChild(issuedIn);
    cells.push(issued);
    // Expiry Date
    const expiry = document.createElement('td');
    const expiryIn = document.createElement('input');
    expiryIn.type = 'date';
    expiryIn.name = 'expiry_date[]';
    expiryIn.required = true;
    expiry.appendChild(expiryIn);
    cells.push(expiry);
    // Issued By
    const issuedBy = document.createElement('td');
    const issuedByIn = document.createElement('input');
    issuedByIn.type = 'text';
    issuedByIn.name = 'license_issued[]';
    issuedByIn.required = true;
    issuedBy.appendChild(issuedByIn);
    cells.push(issuedBy);
    // Upload PDF
    const upload = document.createElement('td');
    const fileIn = document.createElement('input');
    fileIn.type = 'file';
    fileIn.name = 'license_file[]';
    fileIn.accept = '.pdf';
    fileIn.required = true;
    upload.appendChild(fileIn);
    cells.push(upload);
    // Actions (remove)
    const act = document.createElement('td');
    const rmBtn = document.createElement('button');
    rmBtn.type = 'button';
    rmBtn.textContent = 'Remove';
    rmBtn.className = 'btn-secondary';
    rmBtn.addEventListener('click', () => {
      licenseTableBody.removeChild(tr);
      Array.from(licenseTableBody.children).forEach((r, i) => (r.children[0].textContent = i + 1));
    });
    act.appendChild(rmBtn);
    cells.push(act);

    const tr = createRow(cells);
    licenseTableBody.appendChild(tr);
  };
  addLicenseBtn.addEventListener('click', addLicenseRow);

  // ---- Form Action Buttons ----
  const form = qs('#annexure2aForm');
  const actionInput = qs('#formAction');
  qs('#saveDraft').addEventListener('click', () => {
    actionInput.value = 'draft';
    form.submit();
  });
  qs('#submitForm').addEventListener('click', () => {
    actionInput.value = 'submit';
    form.submit();
  });

  // ---- Readonly Mode (if server sets) ----
  const isReadOnly = !!window.isReadOnly; // server may inject `window.isReadOnly = true;`
  if (isReadOnly) {
    qsa('input, select, textarea, button').forEach((el) => {
      if (el.type !== 'hidden') el.disabled = true;
    });
  }
});

<!-- Shared Safety Certificate Template -->
<div id="certificateTemplate" style="display:none;">
  <div id="cert-container" style="width: 230mm; height: 140mm; padding: 20px; background: white; border: 12px solid #2563eb; font-family: 'Inter', sans-serif; position: relative; box-sizing: border-box; margin: auto;">
    <div style="border: 2px solid #2563eb; padding: 25px; position: relative; height: 100%; box-sizing: border-box;">
      <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="margin: 0; font-size: 32px; color: #2563eb; text-transform: uppercase; letter-spacing: 2px;">Certificate of Safety Induction</h1>
        <p style="margin: 5px 0; font-size: 16px; color: #64748b;">Contract Labour Management System (CLMS)</p>
      </div>
      <div style="text-align: center; margin: 30px 0;">
        <p style="font-size: 18px; font-style: italic;">This is to certify that</p>
        <h2 id="cert-worker-name" style="font-size: 28px; color: #1e293b; margin: 10px 0; border-bottom: 2px solid #e2e8f0; display: inline-block; padding: 0 40px;"></h2>
        <p style="font-size: 18px; line-height: 1.6; margin-top: 20px;">
          Aadhaar No: <b id="cert-aadhaar"></b> | Trade: <b id="cert-trade"></b><br>
          employed by <b><span id="cert-contractor"></span></b><br>
          has successfully completed the <b>Safety Induction Training</b>
        </p>
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 50px;">
        <div>
          <p style="margin: 5px 0; font-size: 14px;">Training Date: <b id="cert-date"></b></p>
          <p style="margin: 5px 0; font-size: 14px;">Batch No: <b id="cert-batch"></b></p>
        </div>
        <div style="text-align: right;">
          <div style="height: 60px; margin-bottom: 5px;"><i class="fas fa-check-circle" style="font-size: 40px; color: #2563eb; opacity: 0.2;"></i></div>
          <p style="margin: 0; font-weight: 700; border-top: 1px solid #1e293b; display: inline-block; padding-top: 5px;">Authorized Safety Officer</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
async function generateCertificate(r) {
    // Populate Template
    document.getElementById('cert-worker-name').innerText = r.name || r.worker_name || 'N/A';
    document.getElementById('cert-aadhaar').innerText = r.aadhaar || 'N/A';
    document.getElementById('cert-trade').innerText = r.trade || 'Worker';
    document.getElementById('cert-contractor').innerText = r.display_contractor || r.contractor_name || 'N/A';
    
    // Determine training date
    let tDate = r.training_valid_till || r.last_training_date || r.scheduled_date || '';
    if(tDate && tDate.includes('-')) {
        const d = new Date(tDate);
        // If it's valid_till, subtract 1 year to get training date
        if(r.training_valid_till) d.setFullYear(d.getFullYear() - 1);
        tDate = d.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'});
    }
    document.getElementById('cert-date').innerText = tDate;
    document.getElementById('cert-batch').innerText = r.batch_number || 'B-IND-001';

    const element = document.getElementById('cert-container');
    element.style.display = 'block'; // Ensure visibility during capture
    
    const opt = {
        margin:       [10, 10, 10, 10], // 10mm margins
        filename:     `Safety_Certificate_${(r.name || r.worker_name || 'Worker').replace(/\s+/g, '_')}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' },
        pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
    };

    try {
        await html2pdf().set(opt).from(element).save();
    } catch (e) {
        console.error('PDF Generation Error:', e);
        alert('Failed to generate PDF. Please try again.');
    } finally {
        element.style.display = 'none'; // Keep it hidden from UI
    }
}
</script>

/**
 * passLimitValidator.js
 * Client-side Annexure 5/A validation.
 * Fetches limits from API and provides real-time enforcement.
 *
 * Usage:
 *   <script src="../../js/passLimitValidator.js"></script>
 *   // Then before enrollment:
 *   const ok = await checkPassLimit('Supervisor', 3);
 *   if (!ok) return; // blocked
 */

const PassLimitValidator = (() => {
  let _limits = null;
  let _contractorId = null;

  /**
   * Fetch limits from the server for the current contractor.
   */
  async function fetchLimits(contractorId) {
    _contractorId = contractorId;
    try {
      const url = contractorId
        ? `../../api/welfare/get_pass_limits.php?contractor_id=${contractorId}`
        : `../../api/welfare/get_pass_limits.php`;
      const res = await fetch(url);
      const result = await res.json();
      if (result.success) {
        _limits = Array.isArray(result.data) ? result.data : (result.data?.pass_limits || []);
      }
    } catch (err) {
      console.warn('PassLimitValidator: Failed to fetch limits', err);
      _limits = [];
    }
    return _limits;
  }

  /**
   * Get limit info for a specific pass type.
   * @param {string} passType - Contractor|Representative|Supervisor|Workman
   * @returns {object|null} { pass_type, allowed, current, rule, utilization }
   */
  function getLimit(passType) {
    if (!_limits || !Array.isArray(_limits)) return null;
    return _limits.find(l => l.pass_type === passType) || null;
  }

  /**
   * Validate if adding `count` entries of `passType` is allowed.
   * Shows alert if blocked.
   * @param {string} passType
   * @param {number} count - Number being added (default 1)
   * @returns {boolean} true if allowed
   */
  function validate(passType, count = 1) {
    const limit = getLimit(passType);

    // No limit data = allow (fail-open for UX, backend will catch)
    if (!limit) return true;

    // No max = unlimited
    if (limit.allowed === null || limit.allowed === undefined) return true;

    const projected = (limit.current || 0) + count;
    if (projected > limit.allowed) {
      const msg = `⚠️ Annexure 5/A Limit Exceeded!\n\n` +
        `Pass Type: ${passType}\n` +
        `Current: ${limit.current}\n` +
        `Maximum Allowed: ${limit.allowed}\n` +
        `Trying to add: ${count}\n\n` +
        `Rule: ${limit.rule}\n\n` +
        `Contact Welfare Admin for override.`;
      if (typeof Swal !== 'undefined' && Swal.fire) {
        Swal.fire({
          icon: 'warning',
          title: 'Enrollment Limit Reached',
          html: `
            <div style="text-align:left;line-height:1.6">
              <p style="margin:0 0 12px;">This enrollment cannot be submitted because the Welfare Admin limit for <strong>${passType}</strong> passes has already been reached.</p>
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;">
                <div><strong>Allowed:</strong> ${limit.allowed}</div>
                <div><strong>Already Enrolled:</strong> ${limit.current || 0}</div>
                <div><strong>New Request:</strong> ${count}</div>
                <div><strong>Rule:</strong> ${limit.rule || 'Fixed limit'}</div>
              </div>
              <p style="margin:12px 0 0;color:#64748b;">Please contact Welfare Admin if an exception or override is required.</p>
            </div>`,
          confirmButtonText: 'Understood',
          confirmButtonColor: '#1e293b'
        });
      } else {
        alert(msg);
      }
      return false;
    }

    return true;
  }

  /**
   * Render a limit summary widget into a container element.
   * @param {HTMLElement} container
   */
  function renderSummary(container) {
    if (!container) return;
    if (!_limits || _limits.length === 0) {
      container.innerHTML = '<p style="color:var(--text-muted,#64748b);font-size:13px;">Pass limits are not configured yet.</p>';
      return;
    }

    let html = `<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;">`;

    _limits.forEach(l => {
      const allowed = l.allowed ?? '∞';
      const current = l.current ?? 0;
      const util = l.utilization ?? 0;
      const barColor = util > 90 ? '#ef4444' : (util > 70 ? '#f59e0b' : '#10b981');
      const typeColors = {
        'Contractor': '#6366f1',
        'Representative': '#f59e0b',
        'Supervisor': '#3b82f6',
        'Workman': '#10b981'
      };

      html += `
        <div style="padding:14px;border-radius:12px;background:rgba(255,255,255,.03);border:1px solid rgba(148,163,184,.15);">
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:${typeColors[l.pass_type] || '#94a3b8'};margin-bottom:6px;">
            ${l.pass_type}
          </div>
          <div style="font-size:22px;font-weight:700;margin-bottom:4px;">
            ${current} <span style="font-size:13px;color:var(--text-muted,#94a3b8);font-weight:400;">/ ${allowed}</span>
          </div>
          <div style="width:100%;background:rgba(148,163,184,.15);border-radius:4px;height:6px;overflow:hidden;margin-bottom:4px;">
            <div style="width:${Math.min(util, 100)}%;background:${barColor};height:100%;border-radius:4px;transition:.3s;"></div>
          </div>
          <div style="font-size:10px;color:var(--text-muted,#94a3b8);">${l.rule || 'No rule'}</div>
        </div>
      `;
    });

    html += `</div>`;
    container.innerHTML = html;
  }

  // Public API
  return {
    fetchLimits,
    getLimit,
    validate,
    renderSummary
  };
})();


// =============================================
// WORKFLOW.JS - Shared CRUD for Form System
// =============================================
// Vanilla JS utils for dynamic forms with app_id flow

const WORKFLOW = {
  // Config: API endpoints per form type
  endpoints: {
    annexure2a: { get: 'get_annexure2a.php', save: 'save_annexure2a.php', delete: 'delete_annexure2a.php' },
    // Add more: annexure3a, welfare_verification, etc.
  },

  // ===== LOAD FORM DATA =====
  async loadForm(formType, appId) {
    if (!appId) appId = getAppId();
    if (!appId) {
      console.warn('[WORKFLOW] No appId provided for loadForm');
      return null;
    }

    try {
      showLoading(true);
      console.log(`📥 Loading ${formType} app_id:`, appId);
      
      const endpoint = this.endpoints[formType]?.get;
      if (!endpoint) throw new Error(`No GET endpoint for ${formType}`);

      const response = await apiFetch(`${endpoint}?application_id=${encodeURIComponent(appId)}`, { method: 'GET' });
      
      console.log(`✅ Loaded ${formType}:`, response);
      
      if (response && response.success && response.data) {
        this.populateForm(response.data);
        return response.data;
      } else {
        console.warn('No data found, new form mode');
        this.clearForm();
        return null;
      }
    } catch (error) {
      console.error(`❌ Load ${formType} failed:`, error);
      showToast('❌', `Failed to load form: ${error.message}`);
      this.clearForm();
      return null;
    } finally {
      showLoading(false);
    }
  },

  // ===== POPULATE FORM =====
  populateForm(data) {
    if (!data) return;

    // Generic: data-id → value mapping
    document.querySelectorAll('[data-bind]').forEach(el => {
      const field = el.dataset.bind;
      let value = data[field];

      // Handle missing/undefined
      if (value === undefined || value === null) {
        value = '';
      }

      // Special handling for dates
      if (field.includes('date') || field === 'created_at') {
        // If it's an input type="date", we need YYYY-MM-DD
        if (el.type === 'date' && value) {
          try {
            const d = new Date(value);
            if (!isNaN(d.getTime())) {
              value = d.toISOString().split('T')[0];
            }
          } catch (e) {
            console.warn('Date parse error:', value);
          }
        }
      }

      el.value = value;
      if (value !== '') el.classList.add('populated');
    });
    
    // Status badges
    const statusEl = document.querySelector('.status-badge');
    if (statusEl && data.workflow_status) {
      const status = data.workflow_status;
      statusEl.textContent = status.replace(/_/g, ' ').toUpperCase();
      const map = statusEl.dataset.map ? JSON.parse(statusEl.dataset.map) : {};
      statusEl.className = `badge badge-${map[status] || 'info'}`;
    }
    
    console.log('✅ Form populated');
  },

  // ===== CLEAR FORM (new mode) =====
  clearForm() {
    document.querySelectorAll('input[data-bind], select[data-bind], textarea[data-bind]').forEach(el => {
      el.value = '';
      el.classList.remove('populated');
    });
    document.querySelectorAll('.status-badge').forEach(el => {
      el.textContent = 'DRAFT';
      el.className = 'badge badge-warning';
    });
  },

  // ===== SAVE/UPDATE FORM =====
  async saveForm(formType, appId = null) {
    if (!appId) appId = getAppId();

    try {
      showLoading(true);
      console.log(`💾 Saving ${formType} app_id:`, appId);

      const formData = this.collectFormData();
      if (!this.validateForm(formData)) return false;

      const endpoint = this.endpoints[formType]?.save;
      if (!endpoint) throw new Error(`No SAVE endpoint for ${formType}`);

      const result = await apiFetch(endpoint, {
        body: { ...formData, application_id: appId }
      });

      console.log(`📤 Save response:`, result);

      if (result && result.success) {
        showToast('✅', result.message || 'Saved successfully!');
        if (result.application_id) {
          setAppId(result.application_id);
          // Optional: Update URL without reload or with reload
          const url = new URL(window.location);
          url.searchParams.set('app_id', result.application_id);
          window.history.pushState({}, '', url);
        }
        return result.application_id || true;
      } else {
        throw new Error(result?.message || 'Save failed');
      }
    } catch (error) {
      console.error(`❌ Save failed:`, error);
      showToast('❌', `Save failed: ${error.message}`);
      return false;
    } finally {
      showLoading(false);
    }
  },

  // ===== DELETE FORM =====
  async deleteForm(formType, appId) {
    if (!appId) appId = getAppId();
    if (!appId) return;

    if (!confirm('Delete this application?')) return false;
    
    try {
      showLoading(true);
      const endpoint = this.endpoints[formType]?.delete;
      if (!endpoint) throw new Error(`No DELETE endpoint for ${formType}`);

      const result = await apiFetch(endpoint, {
        body: { application_id: appId }
      });

      if (result && result.success) {
        showToast('🗑️', 'Deleted successfully!');
        setTimeout(() => {
          window.location.href = 'contractor-dashboard.php';
        }, 1000);
        return true;
      } else {
        throw new Error(result?.message || 'Delete failed');
      }
    } catch (error) {
      console.error('❌ Delete failed:', error);
      showToast('❌', `Delete failed: ${error.message}`);
      return false;
    } finally {
      showLoading(false);
    }
  },

  // ===== COLLECT FORM DATA =====
  collectFormData() {
    const data = {};
    document.querySelectorAll('[data-bind]').forEach(el => {
      data[el.dataset.bind] = el.value.trim();
    });
    // Add metadata
    data.form_updated = new Date().toISOString();
    return data;
  },

  // ===== VALIDATE FORM =====
  validateForm(data) {
    const errors = [];
    
    // Check for elements with [required] attribute among those with [data-bind]
    document.querySelectorAll('[data-bind][required]').forEach(el => {
      const field = el.dataset.bind;
      if (!data[field]) {
        const label = el.closest('.form-group')?.querySelector('label')?.textContent || field;
        errors.push(`${label} is required`);
      }
    });

    if (errors.length > 0) {
      showToast('⚠️', errors[0]); 
      return false;
    }
    
    return true;
  },

  // ===== WORKFLOW STEPPER =====
  async getStatus(appId) {
    try {
      return await apiFetch(`get_application_status.php?application_id=${encodeURIComponent(appId)}`, { method: 'GET' });
    } catch (err) {
      console.error('Failed to get status:', err);
      return { success: false, message: err.message };
    }
  },

  updateStepper(steps) {
    if (!steps || !Array.isArray(steps)) return;
    
    const stepMap = ['step1', 'step2', 'step3', 'step4', 'step5'];
    
    stepMap.forEach((stepId, index) => {
      const stepEl = document.getElementById(stepId);
      if (!stepEl) return;
      
      if (index < steps.length && steps[index]?.completed) {
        stepEl.classList.add('completed');
        stepEl.classList.remove('active', 'pending');
      } else if (index < steps.length && steps[index]?.active) {
        stepEl.classList.add('active');
        stepEl.classList.remove('completed', 'pending');
      } else {
        stepEl.classList.add('pending');
        stepEl.classList.remove('completed', 'active');
      }
    });
  }
};

// ===== UX HELPERS =====
function showLoading(show = true) {
  const overlay = document.getElementById('loading-overlay') || createLoadingOverlay();
  if (overlay) overlay.style.display = show ? 'flex' : 'none';
}

function createLoadingOverlay() {
  let overlay = document.getElementById('loading-overlay');
  if (overlay) return overlay;

  overlay = document.createElement('div');
  overlay.id = 'loading-overlay';
  overlay.style.cssText = `
    position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);
    display:none;align-items:center;justify-content:center;z-index:9999;
  `;
  overlay.innerHTML = `
    <div style="background:white;padding:24px;border-radius:12px;text-align:center;box-shadow:0 10px 25px rgba(0,0,0,0.2);">
      <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;">
        <span class="visually-hidden">Loading...</span>
      </div>
      <div style="margin-top:15px;font-weight:600;color:#333;">Processing Request...</div>
    </div>
  `;
  document.body.appendChild(overlay);
  return overlay;
}

// Ensure showToast is available or use fallback
const _showToast = (icon, message) => {
  if (typeof window.showToast === 'function') {
    window.showToast(icon, message);
  } else {
    console.log(`[TOAST] ${icon} ${message}`);
    alert(`${icon} ${message}`);
  }
};

// ===== AUTO INIT =====
document.addEventListener('DOMContentLoaded', async function() {
  try {
    const urlParams = new URLSearchParams(window.location.search);
    let appId = urlParams.get('app_id') || urlParams.get('application_id') || getAppId();
    const formType = document.body.dataset.formType;
    
    if (appId && appId !== 'null' && appId !== 'undefined') {
      setAppId(appId); // Sync to storage
      
      if (formType) {
        await WORKFLOW.loadForm(formType, appId);
      }
      
      if (document.querySelector('.stepper') || document.getElementById('step1')) {
        const statusRes = await WORKFLOW.getStatus(appId);
        if (statusRes && statusRes.success && statusRes.steps) {
          WORKFLOW.updateStepper(statusRes.steps);
        }
      }
    }
    
    // Global Action Listener
    document.addEventListener('click', async e => {
      const btn = e.target.closest('[data-workflow-action]');
      if (btn) {
        const action = btn.dataset.workflowAction;
        const currentAppId = btn.dataset.appId || getAppId();
        const currentFormType = document.body.dataset.formType || btn.dataset.formType;
        
        try {
          switch(action) {
            case 'save': await WORKFLOW.saveForm(currentFormType, currentAppId); break;
            case 'delete': await WORKFLOW.deleteForm(currentFormType, currentAppId); break;
            case 'load': await WORKFLOW.loadForm(currentFormType, currentAppId); break;
          }
        } catch (err) {
          console.error(`Action ${action} failed:`, err);
        }
      }
    });
  } catch (err) {
    console.error('Workflow Init Error:', err);
  }
});

window.WORKFLOW = WORKFLOW;
console.log('✅ workflow.js initialized');



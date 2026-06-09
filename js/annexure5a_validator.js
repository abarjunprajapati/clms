/**
 * ANNEXURE 5/A - Frontend Pass Limit Validator
 * 
 * यह JavaScript file real-time validation provide करता है
 * form submission से पहले
 * 
 * Usage:
 *   <script src="/js/annexure5a_validator.js"></script>
 *   
 *   const result = validatePassLimit({
 *       pass_type: 'Supervisor',
 *       requested: 3,
 *       workmen_count: 35,
 *       current_count: 1
 *   });
 *   
 *   if (!result.valid) {
 *       alert(result.message);
 *   }
 */

class PassLimitValidator {
    /**
     * Configuration - Default Rules from Annexure 5/A
     */
    static RULES = {
        'Contractor': {
            type: 'Fixed',
            max_allowed: 2,
            rule_text: 'Maximum 2 contractors per firm'
        },
        'Representative': {
            type: 'Fixed',
            max_allowed: 1,
            rule_text: 'Only 1 representative per firm'
        },
        'Supervisor': {
            type: 'Ratio',
            ratio_per_workmen: 10,
            rule_text: '1 supervisor per 10 workmen + 1 additional'
        },
        'Workman': {
            type: 'NoLimit',
            rule_text: 'No fixed limit'
        }
    };

    /**
     * Calculate allowed count based on pass type and workmen
     */
    static calculateAllowed(pass_type, workmen_count = 0) {
        const rule = this.RULES[pass_type];
        
        if (!rule) {
            return { allowed: null, message: 'Unknown pass type' };
        }

        switch (pass_type) {
            case 'Contractor':
                return {
                    allowed: rule.max_allowed,
                    message: `Max ${rule.max_allowed} allowed`
                };

            case 'Representative':
                return {
                    allowed: rule.max_allowed,
                    message: `Only ${rule.max_allowed} allowed`
                };

            case 'Supervisor':
                const allowed = workmen_count > 0 
                    ? Math.floor(workmen_count / rule.ratio_per_workmen) + 1
                    : 1;
                return {
                    allowed: allowed,
                    message: `Based on ${workmen_count} workmen: ${allowed} supervisors allowed`,
                    calculation: `Math.floor(${workmen_count} / ${rule.ratio_per_workmen}) + 1 = ${allowed}`
                };

            case 'Workman':
                return {
                    allowed: null,
                    message: 'No fixed limit'
                };

            default:
                return { allowed: null, message: 'Unknown type' };
        }
    }

    /**
     * MAIN VALIDATION FUNCTION
     * 
     * @param {Object} params
     * @returns {Object} { valid, message, current, allowed, requested }
     */
    static validate(params) {
        const {
            pass_type,
            requested = 1,
            workmen_count = 0,
            current_count = 0,
            allow_override = false
        } = params;

        // Input validation
        if (!pass_type) {
            return { valid: false, message: '❌ Pass type not specified' };
        }

        if (!this.RULES[pass_type]) {
            return { valid: false, message: `❌ Invalid pass type: ${pass_type}` };
        }

        const calc = this.calculateAllowed(pass_type, workmen_count);
        const allowed = calc.allowed;

        const result = {
            pass_type: pass_type,
            requested: requested,
            current_count: current_count,
            allowed: allowed,
            total_after: current_count + requested,
            valid: true,
            message: '✅ ' + calc.message,
            rule: this.RULES[pass_type].rule_text
        };

        // No limit = always valid
        if (allowed === null) {
            return result;
        }

        // Check if adding would exceed limit
        if ((current_count + requested) > allowed) {
            result.valid = false;
            result.message = `❌ Cannot add ${requested} ${pass_type}(s). ` +
                `Current: ${current_count}, Allowed: ${allowed}, ` +
                `Total would be: ${current_count + requested}`;
            
            if (allow_override) {
                result.message += '\n(⚠️ Can be overridden by Welfare Admin)';
                result.can_override = true;
            }
            
            return result;
        }

        return result;
    }

    /**
     * Validate supervisor count based on workmen
     */
    static validateSupervisor(workmen_count, supervisor_count) {
        const allowed = this.calculateAllowed('Supervisor', workmen_count).allowed;
        
        return {
            workmen: workmen_count,
            supervisors_requested: supervisor_count,
            supervisors_allowed: allowed,
            valid: supervisor_count <= allowed,
            message: supervisor_count <= allowed
                ? `✅ ${supervisor_count} supervisors OK for ${workmen_count} workmen`
                : `❌ ${supervisor_count} supervisors exceeds limit of ${allowed} for ${workmen_count} workmen`
        };
    }

    /**
     * Get real-time message for UI feedback
     */
    static getUIMessage(pass_type, current, requested, workmen_count = 0) {
        const result = this.validate({
            pass_type,
            requested,
            current_count: current,
            workmen_count
        });

        return {
            text: result.message,
            valid: result.valid,
            class: result.valid ? 'text-success' : 'text-danger',
            icon: result.valid ? '✅' : '❌'
        };
    }

    /**
     * Generate validation summary for display
     */
    static generateSummary() {
        const summary = {
            title: 'ANNEXURE 5/A - Pass Limit Rules',
            timestamp: new Date().toLocaleString(),
            rules: []
        };

        for (const [pass_type, rule] of Object.entries(this.RULES)) {
            summary.rules.push({
                pass_type: pass_type,
                type: rule.type,
                rule_text: rule.rule_text,
                max_allowed: rule.max_allowed || 'Unlimited',
                ratio: rule.ratio_per_workmen ? `1:${rule.ratio_per_workmen}` : 'N/A'
            });
        }

        return summary;
    }
}

// ============ EXAMPLE USAGE ============
/*
// Example 1: Adding supervisors
const result1 = PassLimitValidator.validate({
    pass_type: 'Supervisor',
    requested: 2,
    workmen_count: 35,
    current_count: 1
});
console.log(result1);
// ✅ Valid

// Example 2: Exceeding contractor limit
const result2 = PassLimitValidator.validate({
    pass_type: 'Contractor',
    requested: 1,
    current_count: 2
});
console.log(result2);
// ❌ Cannot add

// Example 3: Quick supervisor check
const supervisorCheck = PassLimitValidator.validateSupervisor(25, 3);
console.log(supervisorCheck);
// ✅ 3 supervisors OK for 25 workmen (allowed: 3)
*/

// Export for use in Node.js/modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PassLimitValidator;
}


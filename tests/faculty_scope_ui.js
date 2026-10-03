// Focused DOM simulations of the actual form functions; not a browser substitute.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
let checks = 0;
function check(value, message) { assert.ok(value, message); checks++; }
function select(records, selected = '') {
    const options = [{ value: '', dataset: {} }, ...records.map(([value, dataset = {}]) => ({ value, dataset }))];
    return { options, value: selected, disabled: false, required: false, listeners: {},
        get selectedOptions() { return this.options.filter(option => option.value === this.value); },
        addEventListener(name, listener) { this.listeners[name] = listener; } };
}
async function run() {
    const department = select([['1', { division: 'IBED' }], ['4', { division: 'COLLEGE' }]], '4');
    const education = select([['1', { departmentId: '1' }], ['2', { departmentId: '1' }], ['3', { departmentId: '1' }], ['4', { departmentId: '4' }]], '4');
    const program = select([['5', { educationLevelId: '4' }], ['6', { educationLevelId: '4' }]], '5');
    const programField = { hidden: false };
    const confirmation = { value: '0' };
    let submissions = 0;
    let resolveConfirmation;
    const form = { listeners: {}, reportValidity: () => true,
        addEventListener(name, listener) { this.listeners[name] = listener; },
        querySelector: () => confirmation };
    const container = { querySelector: selector => ({
        '[name="department_id"]': department,
        '[name="education_level_id"]': education,
        '[name="academic_program_id"]': program,
        '[data-faculty-program-field]': programField
    })[selector] };
    const adminSource = fs.readFileSync('Assets/js/manage-users.js', 'utf8');
    const assignmentFunction = adminSource.slice(adminSource.indexOf('function setupFacultyAssignments()'), adminSource.indexOf('function setupFacultyProvisioning()'));
    const context = vm.createContext({
        document: { querySelectorAll: () => [container], getElementById: () => form },
        requestConfirmation: () => new Promise(resolve => { resolveConfirmation = resolve; }),
        submitForm: () => { submissions++; }
    });
    vm.runInContext(assignmentFunction + '\nsetupFacultyAssignments();', context);
    check(program.required && !program.disabled && !programField.hidden, 'College program required and visible');
    check(education.options.find(o => o.value === '2').disabled, 'IBED education unavailable for College assignment');
    department.value = '1'; department.listeners.change();
    check(education.value === '' && program.value === '', 'Changing division clears incompatible saved values');
    check(program.disabled && !program.required && programField.hidden, 'IBED assignment has no College program');
    education.value = '2'; education.listeners.change();
    check(education.value === '2', 'Junior High assignment retained');
    const event = { preventDefault() {} };
    const cancelled = form.listeners.submit(event); resolveConfirmation(false); await cancelled;
    check(submissions === 0 && confirmation.value === '0', 'Cancel does not submit');
    const pending = form.listeners.submit(event);
    await form.listeners.submit(event);
    resolveConfirmation(true); await pending;
    check(submissions === 1 && confirmation.value === '1', 'Confirmation submits once despite repeated click');
    await form.listeners.submit(event);
    check(submissions === 1, 'No second submission while navigation is pending');

    const postingSource = fs.readFileSync('Assets/js/posting.js', 'utf8');
    const filteringFunctions = postingSource.slice(postingSource.indexOf('function findAcademicRecord('), postingSource.indexOf('function reindexAcademicScopeRows()'));
    function postingScenario(scope, faculty) {
        const fields = {
            department: select([['1'], ['4']], faculty ? '1' : '4'),
            education: select([['2', { departmentId: '1' }], ['3', { departmentId: '1' }], ['4', { departmentId: '4' }]], faculty ? '3' : '4'),
            program: select([['1', { educationLevelId: '3' }], ['5', { educationLevelId: '4' }], ['6', { educationLevelId: '4' }]], faculty ? '6' : ''),
            grade: select([['7', { educationLevelId: '2' }], ['11', { educationLevelId: '3' }], ['13', { educationLevelId: '4' }]]),
            section: select([['7', { gradeLevelId: '7', programId: '' }], ['11', { gradeLevelId: '11', programId: '1' }], ['19', { gradeLevelId: '13', programId: '5' }], ['20', { gradeLevelId: '13', programId: '6' }]])
        };
        const ctx = vm.createContext({ fields, isFacultyUser: faculty, assignedFacultyScope: scope,
            getAcademicScopeElements: () => fields,
            academicGradeLevels: [{ grade_level_id: 7, education_level_id: 2 }, { grade_level_id: 11, education_level_id: 3 }, { grade_level_id: 13, education_level_id: 4 }],
            academicEducationLevels: [{ education_level_id: 2, department_id: 1 }, { education_level_id: 3, department_id: 1 }, { education_level_id: 4, department_id: 4 }]
        });
        vm.runInContext(filteringFunctions + '\nfilterAcademicScopeRow({});', ctx);
        return fields;
    }
    let fields = postingScenario({ department_id: 4, education_level_id: 4, academic_program_id: 5 }, true);
    check(fields.department.value === '4' && fields.education.value === '4' && fields.program.value === '5', 'College scope overrides incompatible draft values');
    check(fields.department.disabled && fields.education.disabled && fields.program.disabled, 'College boundaries locked');
    check(!fields.section.options.find(o => o.value === '19').disabled && fields.section.options.find(o => o.value === '20').disabled, 'Only own-program sections selectable');
    fields = postingScenario({ department_id: 1, education_level_id: 2, academic_program_id: null }, true);
    check(fields.education.value === '2' && fields.education.disabled, 'Junior High boundary locked');
    check(fields.section.options.find(o => o.value === '11').disabled, 'Senior High section unavailable to Junior High');
    fields = postingScenario({ department_id: 1, education_level_id: 3, academic_program_id: null }, true);
    check(!fields.program.disabled && !fields.program.options.find(o => o.value === '1').disabled, 'Senior High may narrow by strand');
    fields = postingScenario({}, false);
    check(!fields.department.disabled && !fields.education.disabled && !fields.program.disabled, 'Admin academic controls remain editable');
    console.log(`PASS: ${checks} Faculty form behavior checks.`);
}
run().catch(error => { console.error(error); process.exitCode = 1; });

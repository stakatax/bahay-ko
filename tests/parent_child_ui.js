// Focused execution of the actual form functions; not a visual browser test.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
let checks = 0;
const check = (ok, message) => { assert.ok(ok, message); checks++; };
const source = fs.readFileSync('Assets/js/register.js', 'utf8');
let role = 'Parent';
const fields = ['child_name','child_section_id','child_reason','child_reason_details'].map(name => ({
    name, value: '', disabled: true, required: false,
    hasAttribute: () => name !== 'child_reason_details'
}));
const ids = {
    childNoAccount: { checked: false },
    childRegistrationDetails: { hidden: true, querySelectorAll: () => fields },
    childStudentId: { required: false }, childStudentIdLabel: {}, childStudentIdHint: {}
};
const context = vm.createContext({ document: { getElementById: id => ids[id] }, getActiveRole: () => role });
const begin = source.indexOf('    function updateChildRegistration()');
const end = source.indexOf("    document.getElementById('childNoAccount')", begin);
vm.runInContext(source.slice(begin, end), context);
const sync = () => vm.runInContext('updateChildRegistration()', context);
sync(); check(ids.childStudentId.required && ids.childRegistrationDetails.hidden, 'existing account path requires ID');
ids.childNoAccount.checked = true; sync();
check(!ids.childStudentId.required && !ids.childRegistrationDetails.hidden, 'new path optional Student ID');
check(fields.slice(0,3).every(f => f.required && !f.disabled), 'required child fields enabled');
check(!fields[3].required && !fields[3].disabled, 'reason explanation optional');
fields[0].value = 'Restored child';
role = 'Student'; sync();
check(fields.every(f => !f.required && f.disabled) && !ids.childStudentId.required, 'Student role excludes Parent inputs');
role = 'Parent'; sync(); check(fields[0].value === 'Restored child', 'role switching preserves typed child details');
ids.childNoAccount.checked = false; sync(); check(fields.every(f => f.disabled) && ids.childStudentId.required, 'switching back restores original requirements');
const admin = fs.readFileSync('Assets/js/account-approvals.js','utf8');
const adminFields = Object.fromEntries(['child_action','child_name','child_student_id','child_section_id','link_student_id'].map(name => [name,{ value: '', listeners: {}, addEventListener(event, fn) { this.listeners[event] = fn; } }]));
const action = adminFields.child_action; action.value = 'update'; action.options = [{textContent:'Update'}]; action.selectedIndex = 0;
const button = {disabled:false}; const listeners = {}; let confirm = false;
const form = { elements: { namedItem: name => adminFields[name] }, querySelector: () => button, addEventListener: (event, fn) => { listeners[event] = fn; } };
vm.runInNewContext(admin.slice(admin.indexOf('// Child verification shares')), {
    document: { addEventListener: (event, fn) => fn(), querySelector: () => form }, window: {confirm: () => confirm}
});
check(adminFields.child_name.required && adminFields.child_section_id.required && adminFields.link_student_id.disabled, 'update validates class/name');
action.value='revoke';action.listeners.change();check(adminFields.child_section_id.disabled && adminFields.link_student_id.disabled, 'revocation works when previous class is unavailable');
action.value='link';action.listeners.change();check(adminFields.link_student_id.required && !adminFields.link_student_id.disabled && adminFields.child_name.disabled, 'link validates only Student ID');
let cancelled=false;listeners.submit({preventDefault(){cancelled=true;}});check(cancelled && !button.disabled,'cancel preserves form');
confirm=true;listeners.submit({preventDefault(){throw Error('unexpected cancellation');}});check(button.disabled,'confirmed submit prevents duplicate click');
console.log(`PASS: ${checks} Parent form behavior checks. DOM simulations; visual browser review remains.`);

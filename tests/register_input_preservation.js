const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync('Assets/js/register.js', 'utf8').replace(/\r\n/g, '\n');
const helper = source.slice(0, source.indexOf("document.addEventListener('DOMContentLoaded'"));
const begin = source.indexOf("    form.addEventListener(\n        'submit',\n        async");
const end = source.indexOf('    /* ==========================================\n   RESTORE SAFE SERVER INPUT', begin);
assert.ok(begin >= 0 && end > begin);
function fixture(result, networkError = false) {
    const fields = Object.fromEntries(['first_name','last_name','email','birthdate','password','password_confirmation'].map(name => [name, {
        name, type: name.startsWith('password') ? 'password' : 'text', value: name + '-valid', disabled:false,
        setAttribute() {}, classList:{add(){}}, closest(){return null;}, dispatchEvent(){},
        scrollIntoView(){}, focus(){this.focused=true;}
    }]));
    const buttonLabel = {textContent:'Submit Registration', dataset:{}};
    let handler;
    const form = {action:'index.php?page=register_action', checkValidity:()=>true,
        elements:{namedItem:name=>fields[name]}, addEventListener:(name,fn)=>{handler=fn;}};
    const context = vm.createContext({form, registrationSubmitting:false,
        submitButton:{disabled:false,querySelector:()=>buttonLabel}, updatePasswordMatch(){}, getActiveRole:()=> 'Student',
        Swal:{fire:async()=>({isConfirmed:true})}, FormData:class {}, Event:class {},
        fetch:async()=> {if(networkError) throw new Error('offline');return {ok:!!result.success,json:async()=>result};},
        window:{location:{assign:url=>{context.destination=url;}}}
    });
    vm.runInContext(helper + source.slice(begin,end), context);
    return {fields,context,buttonLabel,submit:()=>handler({preventDefault(){}})};
}
(async()=>{
    let item = fixture({success:false,message:'Email is already registered.',error_field:'email'});
    await item.submit();
    assert.equal(item.fields.email.value,'');
    assert.equal(item.fields.email.focused,true);
    for(const name of ['first_name','last_name','birthdate','password','password_confirmation']) assert.equal(item.fields[name].value,name+'-valid');
    assert.equal(item.context.submitButton.disabled,false);
    assert.equal(item.buttonLabel.textContent,'Submit Registration');
    item=fixture({},true);await item.submit();
    for(const field of Object.values(item.fields)) assert.equal(field.value,field.name+'-valid');
    item=fixture({success:true,redirect:'index.php?page=login'});await item.submit();
    assert.equal(item.context.destination,'index.php?page=login');
    assert.equal(item.context.registrationSubmitting,true);
    console.log('PASS: Registration validation clears only the invalid email, focuses it, preserves valid fields/passwords, handles network failure, and redirects on success. DOM simulation only.');
})().catch(error=>{console.error(error);process.exitCode=1;});

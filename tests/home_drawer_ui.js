const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync('Assets/js/news.js', 'utf8');
let activeElement;
const attributes = new Map([['inert', ''], ['aria-hidden', 'true']]);
function classList() {const set=new Set();return {add(v){set.add(v);},remove(v){set.delete(v);},contains(v){return set.has(v);}};}
const trigger={isConnected:true,focus(){activeElement=this;}};
const closeButton={focus(){activeElement=this;}};
const actions={};
let inserted;
const content={querySelector(){return actions;},insertBefore(element,before){inserted=[element,before];}};
const card={dataset:{type:'announcement',contentId:'5'},classList:classList(),querySelector(){return content;}};
const drawer={classList:classList(),removeAttribute(name){attributes.delete(name);},setAttribute(name,value){attributes.set(name,value);}};
activeElement=trigger;
const document={body:{style:{overflow:'auto'}},get activeElement(){return activeElement;}};
const context=vm.createContext({drawer,closeButton,document,hubItems:[card],overlay:null,contentLoadSequence:0,drawerContent:null,
 activeContentType:'announcement',activeContentId:5,activeReaction:null,commentInput:null,
 resetReplyState(){},clearMedia(){},setActiveReaction(){},resetReactionCounts(){}});
const start=source.indexOf('    let drawerReturnFocus');
const end=source.indexOf('    function clearMedia()',start);
vm.runInContext(source.slice(start,end),context);
context.openDrawer();
assert.equal(inserted[0],drawer);assert.equal(inserted[1],actions);
assert.ok(card.classList.contains('is-reader-open'));
assert.equal(attributes.get('aria-hidden'),'false');
assert.equal(document.body.style.overflow,'auto');
context.closeDrawer();
assert.ok(!card.classList.contains('is-reader-open'));
assert.equal(attributes.get('aria-hidden'),'true');
assert.ok(attributes.has('inert'));assert.equal(activeElement,trigger);
context.activeContentType='announcement';context.activeContentId=999;
inserted=null;context.openDrawer();assert.equal(inserted,null);
assert.equal(document.body.style.overflow,'auto');
console.log('PASS: inline post expansion, collapse, focus return and unavailable-post guard; no scroll locking.');

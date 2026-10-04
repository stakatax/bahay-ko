const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync('Assets/js/news.js', 'utf8').replace(/\r\n/g, '\n');
const counts = {Upvote:{textContent:0},Downvote:{textContent:0}};
const buttons=['Upvote','Downvote'].map(vote=>({dataset:{feedVote:vote},disabled:false,pressed:'false',
 setAttribute(name,value){this.pressed=value;},querySelector(){return counts[vote];},
 addEventListener(name,callback){this.click=callback;}}));
const group={dataset:{voteType:'announcement',voteId:'5'},busy:'false',
 setAttribute(name,value){this.busy=value;},querySelectorAll(){return buttons;}};
let writes=0,release;
const payloads=[];
const context=vm.createContext({
 document:{querySelectorAll(selector){return selector==='[data-feed-votes]'?[group]:[];}},
 FormData:class {constructor(){this.values={};}append(name,value){this.values[name]=value;}},
 async sendRequest(action,form){writes++;payloads.push([action,form.values]);await new Promise(resolve=>release=resolve);
 return {engagement:{reaction_breakdown:{Upvote:1,Downvote:0},reaction_count:1,user_reaction:'Upvote'}};},
 normalizeReactionCounts(data){return data.reaction_breakdown;},updateCardUserReaction(){},renderRowReactionStack(){},
 activeContentType:null,activeContentId:null,window:{alert(message){throw new Error(message);}}
});
const start=source.indexOf('    // Feed actions use');
const end=source.indexOf('    /* ==========================================\n   UNIFIED REACTIONS',start);
vm.runInContext(source.slice(start,end),context);
(async()=>{
 const pending=buttons[0].click();
 assert.ok(buttons.every(button=>button.disabled));
 await buttons[1].click();assert.equal(writes,1);
 assert.deepEqual(payloads[0],['content_react',{content_type:'announcement',content_id:'5',reaction:'Upvote'}]);
 release();await pending;
 assert.equal(counts.Upvote.textContent,1);assert.equal(counts.Downvote.textContent,0);
 assert.equal(buttons[0].pressed,'true');assert.equal(buttons[1].pressed,'false');
 assert.ok(buttons.every(button=>!button.disabled));assert.equal(group.busy,'false');
 context.syncFeedVotes('announcement',5,{reaction_breakdown:{Upvote:0,Downvote:0},user_reaction:null});
 assert.ok(buttons.every(button=>button.pressed==='false'));
 console.log('PASS: inline vote endpoint, duplicate-tap guard, counts, selection and withdrawal sync.');
})().catch(error=>{console.error(error);process.exitCode=1;});

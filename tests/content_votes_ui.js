const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync('Assets/js/news.js', 'utf8');
function section(start, end) {
    return source.slice(source.indexOf(start), source.indexOf(end, source.indexOf(start)));
}
const nodes = { drawerVoteScore: {textContent: ''} };
const buttons = ['Upvote', 'Downvote'].map(vote => ({
    dataset: {reaction: vote}, pressed: '', active: false,
    classList: {toggle(name, value) { this.owner.active = value; }},
    setAttribute(name, value) { this.pressed = value; }
}));
buttons.forEach(button => button.classList.owner = button);
const context = vm.createContext({
    document: {getElementById(id) { return nodes[id]; }},
    supportedReactions: ['Upvote', 'Downvote'], reactionButtons: buttons,
    activeContentType: null, activeContentId: null, activeReaction: null,
    drawerViewCount: null, drawerReactionCount: null,
    drawerCommentCount: null, drawerAcknowledgmentCount: null,
    renderReactionCounts() {}, syncFeedVotes() {}
});
vm.runInContext(section('    function normalizeReactionCounts(', '    function resetReactionCounts()'), context);
vm.runInContext(section('    function setActiveReaction(', '    function initializeCardUserReactions()'), context);
// Use the real count updater, with no open card so no feed DOM is needed.
const updateStart = source.indexOf('function updateCounts(');
const updateEnd = source.indexOf('    /* ==========================================', updateStart);
vm.runInContext(source.slice(updateStart, updateEnd), context);
const counts = context.normalizeReactionCounts({reaction_breakdown: {Upvote: 4, Downvote: 2}});
assert.equal(counts.Upvote, 4);
assert.equal(counts.Downvote, 2);
assert.ok(!source.includes('drawerVoteScore'));
context.setActiveReaction('Upvote');
assert.equal(buttons[0].pressed, 'true');
assert.equal(buttons[1].pressed, 'false');
context.setActiveReaction('Downvote');
assert.equal(buttons[0].pressed, 'false');
assert.equal(buttons[1].pressed, 'true');
context.setActiveReaction(null);
assert.ok(buttons.every(button => button.pressed === 'false' && !button.active));
context.setActiveReaction('Like');
assert.ok(buttons.every(button => button.pressed === 'false'));
console.log('PASS: separate upvote/downvote counts and accessible selected/withdrawn states.');

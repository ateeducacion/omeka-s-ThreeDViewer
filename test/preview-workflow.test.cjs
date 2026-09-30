// Run with: node --test test/preview-workflow.test.cjs
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');
const { test } = require('node:test');
const { runInNewContext } = require('node:vm');

const workflow = readFileSync(join(__dirname, '../.github/workflows/pr-playground-preview.yml'), 'utf8');

test('preview uses the standard PR trigger and PR comment permissions', () => {
    assert.match(workflow, /^  pull_request:$/m);
    assert.doesNotMatch(workflow, /pull_request_target:/);
    assert.match(workflow, /^  pull-requests: write$/m);
    assert.doesNotMatch(workflow, /^  issues: write$/m);
    assert.match(workflow, /ref: \$\{\{ github.event.pull_request.base.sha \}\}/);
    assert.match(workflow, /persist-credentials: false/);
});

test('preview runs for internal branches and skips forks', () => {
    const condition = workflow.match(/^    if: (.+)$/m);
    assert.ok(condition, 'preview must have a job-level guard');
    for (const headRepo of ['ateeducacion/omeka-s-ThreeDViewer', 'contributor/omeka-s-ThreeDViewer']) {
        const github = {
            repository: 'ateeducacion/omeka-s-ThreeDViewer',
            event: { pull_request: { head: { repo: { full_name: headRepo } } } }
        };
        // The guard uses equality syntax shared by Actions expressions and JavaScript.
        assert.equal(runInNewContext(condition[1], { github }), headRepo === github.repository);
    }
});

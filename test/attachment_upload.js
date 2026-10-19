/* Copyright (C) 2026 Pierre Ardoin; SPDX-License-Identifier: GPL-3.0-or-later */
'use strict';
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(require('node:path').join(__dirname, '../js/diffusion.js.php'), 'utf8');
const script = source.slice(source.indexOf('function diffusionGetAjaxUploadResponseInfo'), source.indexOf('jQuery(document).ready'));
let checks = 0;
function test(value, expected) { assert.deepEqual(value, expected); checks++; }
function fixture(hasContext = true) {
	const filters = [], requests = [];
	const element = {getAttribute: name => name === 'data-id' ? '829' : '2'};
	const jq = {isArray: Array.isArray, ajaxPrefilter: fn => filters.push(fn), ajax: options => {
		const pending = {options, done(fn) {this.success = fn; return this;}, fail(fn) {this.failure = fn; return this;}};
		requests.push(pending); return pending;
	}};
	const context = {jQuery: jq, URL, FormData, document: {getElementById: () => hasContext ? element : null},
		window: {location: {href: 'https://erp.invalid/custom/diffusion/diffusion_agenda.php?id=829&page=2', origin: 'https://erp.invalid'}},
		diffusionFileUploadUrl: '/core/ajax/fileupload.php', diffusionRegenerateDocumentUrl: '/custom/diffusion/ajax/regeneratedocument.php'};
	vm.createContext(context); vm.runInContext(script, context);
	return {context, filters, requests};
}
for (const module of ['order_supplier', 'facture', 'commande', 'project', 'product', 'diffusiondoc']) {
	const f = fixture(module === 'diffusiondoc');
	f.context.diffusionRegisterUploadCompletion();
	f.context.diffusionRegisterUploadCompletion();
	test(f.filters.length, module === 'diffusiondoc' ? 1 : 0);
}
for (const module of ['order_supplier', 'facture', 'commande', 'project', 'product']) {
	const f = fixture(); f.context.diffusionRegisterUploadCompletion();
	const data = new FormData(); data.append('element', module); data.append('fk_element', '829');
	const original = () => {}; const options = {url: '/core/ajax/fileupload.php', data, success: original};
	f.filters[0](options); test(options.success, original); test(f.requests.length, 0);
}
for (const response of ['[]', 'not JSON', [{error: 'Too large'}]]) {
	const f = fixture(); f.context.diffusionRegisterUploadCompletion();
	const data = new FormData(); data.append('element', 'diffusiondoc'); data.append('fk_element', '829');
	const options = {url: '/core/ajax/fileupload.php', data}; f.filters[0](options); options.success(response);
	test(f.requests.length, 0); test(f.context.window.location.href.includes('ErrorUploadFileDragDrop'), true);
}
for (const outcome of ['success', 'failure', 'bad-status']) {
	const f = fixture(); f.context.diffusionRegisterUploadCompletion();
	const data = new FormData(); data.append('element', 'diffusiondoc'); data.append('fk_element', '829'); data.append('token', 'fixture-csrf');
	const options = {url: '/core/ajax/fileupload.php', data}; f.filters[0](options);
	options.success([{name: 'plan électricité.pdf'}, {error: 'Too large'}, {name: 'second.pdf'}]);
	test(f.requests.length, 1);
	test(Array.from(f.requests[0].options.data.files), ['plan électricité.pdf', 'second.pdf']);
	test(f.requests[0].options.data.token, 'fixture-csrf');
	if (outcome === 'failure') f.requests[0].failure(); else f.requests[0].success({status: outcome === 'success' ? 'ok' : 'error'});
	test(f.context.window.location.href.startsWith('/custom/diffusion/diffusion_agenda.php?'), true);
	test(f.context.window.location.href.includes('ErrorOnAtLeastOneFileUpload'), true);
	test(f.context.window.location.href.includes('RegenerationAfterFileChangeFailed'), outcome !== 'success');
}
for (const [url, id] of [['https://another.invalid/core/ajax/fileupload.php', '829'], ['/core/ajax/fileupload.php', '830'], ['/unrelated.php', '829']]) {
	const f = fixture(); f.context.diffusionRegisterUploadCompletion();
	const data = new FormData(); data.append('element', 'diffusiondoc'); data.append('fk_element', id);
	const original = () => {}; const options = {url, data, success: original}; f.filters[0](options);
	test(options.success, original);
}
console.log(`${checks} assertions passed; simulated DOM/Ajax, no network requests.`);

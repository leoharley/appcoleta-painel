/**
 * Helper para popular os <select>s do módulo de IA em cascata:
 *   questionário → respostas / perguntas
 *
 * Uso típico:
 *   AiSelects.questionnaire(document.getElementById('selQuestionnaire'));
 *   AiSelects.linkResponses(selQuestionnaire, selResponse);
 *   AiSelects.linkQuestions(selQuestionnaire, selQuestion, { allowEmpty: true });
 */
(function(global) {
    'use strict';

    // Resolve o base_url do CodeIgniter: a aplicação serve em /painel/
    function baseUrl() {
        const path = window.location.pathname; // ex: /painel/ai/inconsistencies
        const idx = path.indexOf('/ai');
        if (idx > 0) return window.location.origin + path.substring(0, idx + 1); // /painel/
        // fallback
        const m = path.match(/^(\/[^\/]+\/)/);
        return window.location.origin + (m ? m[1] : '/');
    }

    function escapeHtml(s) {
        s = String(s == null ? '' : s);
        return s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function setOptions(sel, opts) {
        sel.innerHTML = opts.map(o =>
            '<option value="' + escapeHtml(o.value) + '">' + escapeHtml(o.label) + '</option>'
        ).join('');
    }

    function formatDate(s) {
        if (!s) return '';
        const d = new Date(s.replace(' ', 'T'));
        if (isNaN(d)) return s;
        const pad = n => String(n).padStart(2, '0');
        return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear()
             + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function fetchJson(path) {
        return fetch(baseUrl() + path, { credentials: 'same-origin' })
            .then(r => r.json())
            .catch(() => ({ success: false }));
    }

    const AiSelects = {

        /** Popula um <select> de questionários. opts: {placeholder, onLoaded(items)} */
        questionnaire: function(sel, opts) {
            opts = opts || {};
            setOptions(sel, [{ value: '', label: 'Carregando…' }]);
            return fetchJson('ai/list_questionnaires').then(r => {
                const items = (r && r.success && r.data) ? r.data : [];
                const list = [{ value: '', label: opts.placeholder || 'Selecione um questionário…' }];
                items.forEach(q => {
                    const proj = q.project_name ? q.project_name + ' · ' : '';
                    list.push({
                        value: q.id,
                        label: proj + q.title +
                               ' (' + (q.responses_count || 0) + ' resp., ' + (q.questions_count || 0) + ' perg.)'
                    });
                });
                setOptions(sel, list);
                if (opts.onLoaded) opts.onLoaded(items);
                return items;
            });
        },

        /**
         * Liga um <select> de respostas (form_response_id) a um <select> de questionário.
         * Quando o questionário muda, recarrega o de respostas.
         */
        linkResponses: function(selQ, selR, opts) {
            opts = opts || {};
            const load = () => {
                const qid = selQ.value;
                if (!qid) {
                    setOptions(selR, [{ value: '', label: 'Selecione um questionário primeiro' }]);
                    selR.disabled = true;
                    return;
                }
                selR.disabled = true;
                setOptions(selR, [{ value: '', label: 'Carregando respostas…' }]);
                fetchJson('ai/list_responses/' + encodeURIComponent(qid)).then(r => {
                    const items = (r && r.success && r.data) ? r.data : [];
                    const list = [{ value: '', label: items.length ? 'Selecione uma resposta…' : 'Sem respostas para este questionário' }];
                    items.forEach(fr => {
                        const parts = [];
                        parts.push('#' + fr.id);
                        if (fr.respondent_name) parts.push(fr.respondent_name);
                        if (fr.applied_by_name) parts.push('por ' + fr.applied_by_name);
                        if (fr.completed_at)    parts.push(formatDate(fr.completed_at));
                        list.push({ value: fr.id, label: parts.join(' · ') });
                    });
                    setOptions(selR, list);
                    selR.disabled = items.length === 0;
                    if (opts.onLoaded) opts.onLoaded(items);
                });
            };
            selQ.addEventListener('change', load);
        },

        /**
         * Liga um <select> de perguntas (question_id) a um <select> de questionário.
         * opts.allowEmpty = adiciona "(todas as perguntas)" no topo.
         */
        linkQuestions: function(selQ, selQuestion, opts) {
            opts = opts || {};
            const load = () => {
                const qid = selQ.value;
                if (!qid) {
                    setOptions(selQuestion, [{ value: '', label: 'Selecione um questionário primeiro' }]);
                    selQuestion.disabled = true;
                    return;
                }
                selQuestion.disabled = true;
                setOptions(selQuestion, [{ value: '', label: 'Carregando perguntas…' }]);
                fetchJson('ai/list_questions/' + encodeURIComponent(qid)).then(r => {
                    const items = (r && r.success && r.data) ? r.data : [];
                    const list = [];
                    if (opts.allowEmpty) list.push({ value: '', label: '— Todas as perguntas —' });
                    else list.push({ value: '', label: items.length ? 'Selecione uma pergunta…' : 'Sem perguntas neste questionário' });
                    items.forEach((q, i) => {
                        const txt = q.question_text || '(sem texto)';
                        const short = txt.length > 80 ? txt.slice(0, 79) + '…' : txt;
                        list.push({
                            value: q.id,
                            label: 'Q' + (q.order_index || i + 1) + ' [' + (q.question_type || '?') + '] ' + short
                        });
                    });
                    setOptions(selQuestion, list);
                    selQuestion.disabled = items.length === 0;
                    if (opts.onLoaded) opts.onLoaded(items);
                });
            };
            selQ.addEventListener('change', load);
        }
    };

    global.AiSelects = AiSelects;
})(window);

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'dashboard';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Admin routes
$route['admin'] = 'dashboard';
$route['admin/login'] = 'auth/login';
$route['admin/logout'] = 'auth/logout';
$route['admin/dashboard'] = 'dashboard';
$route['admin/questionnaires'] = 'questionnaires';
$route['admin/questionnaires/create'] = 'questionnaires/create';
$route['admin/questionnaires/edit/(:num)'] = 'questionnaires/edit/$1';
$route['admin/questionnaires/duplicate/(:num)'] = 'questionnaires/duplicate/$1';
$route['admin/responses'] = 'responses';
$route['admin/responses/view/(:num)'] = 'responses/view/$1';
$route['admin/responses/export'] = 'responses/export';
$route['admin/users'] = 'users';
$route['admin/reports'] = 'reports';
$route['admin/settings'] = 'settings';

// API routes
$route['api/auth/login'] = 'api/auth/login';
$route['api/auth/verify'] = 'api/auth/verify';
$route['api/questionnaires'] = 'api/questionnaires/index';
$route['api/forms/submit'] = 'api/forms/submit';
$route['api/photos/upload'] = 'api/photos/upload';
$route['api/audios/upload'] = 'api/audios/upload';

// Rotas para API de Estatísticas
$route['api/stats/user/(:num)'] = 'api/stats/user/$1';
$route['api/stats/user'] = 'api/stats/user';
$route['api/stats/overview'] = 'api/stats/overview';

// Novas rotas para Supervisores/Administradores
$route['api/stats/applicators'] = 'api/stats/applicators';
$route['api/stats/locations'] = 'api/stats/locations';

$route['api/stats/history'] = 'api/stats/history';
$route['api/stats/history/(:num)'] = 'api/stats/history/$1';
$route['api/stats/history/(:num)/summary'] = 'api/stats/history_summary/$1';
$route['api/stats/history/summary'] = 'api/stats/history_summary';

$route['api/stats/questionnaires-analysis'] = 'api/stats/questionnaires_analysis';
$route['api/stats/questionnaire-analysis/(:num)'] = 'api/stats/questionnaire_analysis/$1';
$route['api/stats/questions-analysis'] = 'api/stats/questions_analysis';
$route['api/stats/applicators_app'] = 'api/stats/applicators_app';


$route['api/questionnaires/(:num)/questions'] = 'api/responses/questionnaire_questions/$1';
$route['api/responses/raw-data'] = 'api/responses/raw_data';
$route['api/responses/count'] = 'api/responses/count_records';
$route['api/responses/validate-export'] = 'api/responses/validate_export';
$route['api/responses/export-preview'] = 'api/responses/export_preview';
$route['api/responses/export-statistics'] = 'api/responses/export_statistics';
$route['api/responses/log-export'] = 'api/responses/log_export';
$route['api/responses/export-limits'] = 'api/responses/export_limits';
$route['api/responses/export-history'] = 'api/responses/export_history';


$route['responses/export_raw_data'] = 'responses/export_raw_data';

// Rotas adicionais úteis para a funcionalidade
$route['responses/preview_export'] = 'responses/preview_export';
$route['responses/validate_export'] = 'responses/validate_export';
$route['responses/export_status/(:num)'] = 'responses/export_status/$1';

// Rotas para AJAX calls
$route['api/responses/count'] = 'responses/ajax_count_responses';
$route['api/responses/preview'] = 'responses/ajax_preview_export';

$route['api/health'] = 'api/system/health';

// Rotas de Estratificacao (Admin)
$route['stratifications'] = 'stratifications/index';
$route['stratifications/create'] = 'stratifications/create';
$route['stratifications/edit/(:num)'] = 'stratifications/edit/$1';
$route['stratifications/view/(:num)'] = 'stratifications/view/$1';
$route['stratifications/delete/(:num)'] = 'stratifications/delete/$1';
$route['stratifications/ajax_get_questions/(:num)'] = 'stratifications/ajax_get_questions/$1';
$route['stratifications/ajax_get_dimensions/(:num)'] = 'stratifications/ajax_get_dimensions/$1';
$route['stratifications/ajax_get_progress/(:num)'] = 'stratifications/ajax_get_progress/$1';

// API de Estratificacao (App Movel)
$route['api/stratification/(:num)'] = 'api/stratification/index/$1';

// =====================================================================
// Páginas institucionais públicas
// =====================================================================
$route['privacidade']        = 'pages/privacidade';
$route['suporte']            = 'pages/suporte';
$route['exclusao-de-conta']  = 'pages/exclusao_de_conta';

// =====================================================================
// Gráficos & Relatórios
// =====================================================================
$route['charts']                              = 'charts/index';
$route['charts/report/(:num)']                = 'charts/report/$1';
$route['charts/chart_data/(:num)/(:num)']     = 'charts/chart_data/$1/$2';
$route['admin/charts']                        = 'charts/index';
$route['admin/charts/report/(:num)']          = 'charts/report/$1';

// =====================================================================
// Módulo de Inteligência Artificial
// =====================================================================
// Painel admin
$route['admin/ai']             = 'ai/index';
$route['admin/ai/settings']    = 'ai/settings';
$route['admin/ai/prompts']     = 'ai/prompts';
$route['admin/ai/logs']        = 'ai/logs';
// (telas extras das Fases 2+: transcriptions, inconsistencies, corrections, smart_fill,
//  reformulations, adaptive, followup, analysis, charts, reports, directives, security)

// Ações AJAX do admin
$route['ai']                    = 'ai/index';
$route['ai/settings']           = 'ai/settings';
$route['ai/prompts']            = 'ai/prompts';
$route['ai/logs']               = 'ai/logs';
$route['ai/update_setting']     = 'ai/update_setting';
$route['ai/toggle_feature']     = 'ai/toggle_feature';
$route['ai/save_api_key']       = 'ai/save_api_key';
$route['ai/test_connection']    = 'ai/test_connection';
$route['ai/save_prompt']        = 'ai/save_prompt';

// Listagens auxiliares (dropdowns em cascata)
$route['ai/list_questionnaires']        = 'ai/list_questionnaires';
$route['ai/list_responses/(:num)']      = 'ai/list_responses/$1';
$route['ai/list_questions/(:num)']      = 'ai/list_questions/$1';

// Telas Fase 2
$route['admin/ai/transcriptions']  = 'ai/transcriptions';
$route['admin/ai/inconsistencies'] = 'ai/inconsistencies';
$route['admin/ai/corrections']     = 'ai/corrections';
$route['admin/ai/smart_fill']      = 'ai/smart_fill';
$route['admin/ai/reformulations']  = 'ai/reformulations';
$route['admin/ai/adaptive']        = 'ai/adaptive';
$route['admin/ai/followup']        = 'ai/followup';
$route['admin/ai/analysis']        = 'ai/analysis';
$route['admin/ai/view_analysis/(:num)'] = 'ai/view_analysis/$1';
$route['admin/ai/charts']          = 'ai/charts';
$route['admin/ai/reports']         = 'ai/reports';
$route['admin/ai/view_report/(:num)']   = 'ai/view_report/$1';
$route['admin/ai/directives']      = 'ai/directives';
$route['admin/ai/security']        = 'ai/security';

// Ações AJAX adicionais
$route['ai/transcriptions']            = 'ai/transcriptions';
$route['ai/upload_audio']              = 'ai/upload_audio';
$route['ai/process_transcription/(:num)'] = 'ai/process_transcription/$1';
$route['ai/save_transcription_edit']   = 'ai/save_transcription_edit';
$route['ai/import_response_audios']    = 'ai/import_response_audios';
$route['ai/process_pending_transcriptions'] = 'ai/process_pending_transcriptions';
$route['ai/inconsistencies']           = 'ai/inconsistencies';
$route['ai/analyze_inconsistencies']   = 'ai/analyze_inconsistencies';
$route['ai/clear_inconsistencies']     = 'ai/clear_inconsistencies';
$route['ai/resolve_inconsistency']     = 'ai/resolve_inconsistency';
$route['ai/corrections']               = 'ai/corrections';
$route['ai/analyze_corrections']       = 'ai/analyze_corrections';
$route['ai/clear_corrections']         = 'ai/clear_corrections';
$route['ai/review_correction']         = 'ai/review_correction';
$route['ai/smart_fill']                = 'ai/smart_fill';
$route['ai/generate_suggestions']      = 'ai/generate_suggestions';
$route['ai/approve_suggestion']        = 'ai/approve_suggestion';
$route['ai/reject_suggestion']         = 'ai/reject_suggestion';
$route['ai/reformulations']            = 'ai/reformulations';
$route['ai/generate_reformulation']    = 'ai/generate_reformulation';
$route['ai/generate_reformulations_batch'] = 'ai/generate_reformulations_batch';
$route['ai/approve_reformulation']     = 'ai/approve_reformulation';
$route['ai/adaptive']                  = 'ai/adaptive';
$route['ai/generate_adaptive_rules']   = 'ai/generate_adaptive_rules';
$route['ai/clear_adaptive_rules']      = 'ai/clear_adaptive_rules';
$route['ai/approve_adaptive_rule']     = 'ai/approve_adaptive_rule';
$route['ai/reject_adaptive_rule']      = 'ai/reject_adaptive_rule';
$route['ai/restore_adaptive_rule']     = 'ai/restore_adaptive_rule';
$route['ai/followup']                  = 'ai/followup';
$route['ai/generate_followup']         = 'ai/generate_followup';
$route['ai/action_followup']           = 'ai/action_followup';
$route['ai/edit_followup']             = 'ai/edit_followup';
$route['ai/clear_followups']           = 'ai/clear_followups';
$route['ai/analysis']                  = 'ai/analysis';
$route['ai/view_analysis/(:num)']      = 'ai/view_analysis/$1';
$route['ai/generate_analysis']         = 'ai/generate_analysis';
$route['ai/delete_analysis']           = 'ai/delete_analysis';
$route['ai/get_question_detail']       = 'ai/get_question_detail';
$route['ai/charts']                    = 'ai/charts';
$route['ai/generate_charts']           = 'ai/generate_charts';
$route['ai/reports']                   = 'ai/reports';
$route['ai/view_report/(:num)']        = 'ai/view_report/$1';
$route['ai/generate_report']           = 'ai/generate_report';
$route['ai/update_report']             = 'ai/update_report';
$route['ai/get_report_data']           = 'ai/get_report_data';
$route['ai/directives']                = 'ai/directives';
$route['ai/save_directive']            = 'ai/save_directive';
$route['ai/get_directive/(:num)']      = 'ai/get_directive/$1';
$route['ai/toggle_directive']          = 'ai/toggle_directive';
$route['ai/delete_directive']          = 'ai/delete_directive';
$route['ai/security']                  = 'ai/security';
$route['ai/batch_analyze']             = 'ai/batch_analyze';
$route['ai/debug_analyze']             = 'ai/debug_analyze';

// API mobile
$route['api/ai/status']                = 'api/ai/status';
$route['api/ai/transcribe']            = 'api/ai/transcribe';
$route['api/ai/transcriptions_sync']   = 'api/ai/transcriptions_sync';
$route['api/ai/followup_tips']         = 'api/ai/followup_tips';
$route['api/ai/check_inconsistencies'] = 'api/ai/check_inconsistencies';
$route['api/ai/suggest_fill']          = 'api/ai/suggest_fill';
$route['api/ai/standardize']           = 'api/ai/standardize';
$route['api/ai/smart_suggestions']     = 'api/ai/smart_suggestions';
$route['api/ai/reformulate_questions'] = 'api/ai/reformulate_questions';
$route['api/ai/adaptive_next']         = 'api/ai/adaptive_next';
$route['api/ai/follow_up']             = 'api/ai/follow_up';
$route['api/ai/generate_report']       = 'api/ai/generate_report';
$route['api/ai/audit_log']             = 'api/ai/audit_log';
$route['api/ai/audit_log_batch']       = 'api/ai/audit_log_batch';
$route['api/ai/approved_suggestions']  = 'api/ai/approved_suggestions';
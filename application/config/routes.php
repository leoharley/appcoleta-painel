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
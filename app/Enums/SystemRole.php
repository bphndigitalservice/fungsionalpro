<?php

namespace App\Enums;

enum SystemRole: string
{
    case SuperAdmin    = 'super_admin';
    case PanelUser     = 'panel_user';
    case Client        = 'client';
    case Verifier      = 'verifier';
    case Admin         = 'admin';
    case AdminRegional = 'admin-regional';
    case AdminPusat    = 'admin-pusat';
    case AdminSdmBphn  = 'admin-sdm-bphn';
    case AdminInstansi = 'admin-instansi';
}
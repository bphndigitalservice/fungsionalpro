<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ClientStatus: string implements HasLabel
{
    case Active = 'active';
    case NonActive_Resign = 'non_active_resign';
    case NonActive_Suspended = 'non_active_suspended';

    case NonActive_CTLN = 'non_active_ctln';
    case NonActive_StudyLeave = 'non_active_study_leave';

    case NonActive_ExternalAssignment = 'non_active_external_assignment';

    case NonActive_DoesntMeetRoleRequirement = 'non_active_doesnt_meet_role_requirement';

    //case TemporarilyNonActive = 'temporarily_nonactive';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::NonActive_Resign => 'Mengundurkan diri',
            self::NonActive_Suspended => 'Diberhentikan Sementara sebagai PNS',
            self::NonActive_CTLN => 'CTLN',
            self::NonActive_StudyLeave => 'Tugas belajar > 6 Bulan',
            self::NonActive_ExternalAssignment => 'Ditugaskan secara penuh di luar jabatan',
            self::NonActive_DoesntMeetRoleRequirement => 'Tidak Memenuhi Persyaratan Jabatan'
        };
    }
}

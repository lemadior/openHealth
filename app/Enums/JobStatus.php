<?php

namespace App\Enums;

enum JobStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paused = 'paused';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Очікується',
            self::Processing => 'Обробляється',
            self::Paused => 'Призупинено',
            self::Completed => 'Виконано',
            self::Failed => 'Помилка'
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

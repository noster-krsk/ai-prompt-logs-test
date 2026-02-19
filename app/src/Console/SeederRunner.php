<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Database;

final class SeederRunner
{
    private string $seedersPath;

    public function __construct(
        private readonly Database $db
    ) {
        $this->seedersPath = dirname(__DIR__, 2) . '/database/seeders';
    }

    /**
     * Запустить все сиды по порядку.
     */
    public function runAll(): void
    {
        $seeders = $this->getSeederClasses();

        if (empty($seeders)) {
            echo "  Сиды не найдены.\n";
            return;
        }

        foreach ($seeders as $class) {
            $this->runSeeder($class);
        }
    }

    private function runSeeder(string $class): void
    {
        /** @var object{run: callable} $seeder */
        $seeder = new $class();

        try {
            $seeder->run($this->db);
            $shortName = (new \ReflectionClass($class))->getShortName();
            echo "  \033[32m✓\033[0m {$shortName}\n";
        } catch (\Throwable $e) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            echo "  \033[31m✗\033[0m {$shortName}: {$e->getMessage()}\n";
            throw $e;
        }
    }

    /**
     * @return list<class-string>
     */
    private function getSeederClasses(): array
    {
        if (!is_dir($this->seedersPath)) {
            return [];
        }

        $files = glob($this->seedersPath . '/*.php');
        sort($files);

        $classes = [];
        foreach ($files as $file) {
            $className = 'App\\Database\\Seeders\\' . pathinfo($file, PATHINFO_FILENAME);
            if (class_exists($className)) {
                $classes[] = $className;
            }
        }

        return $classes;
    }
}

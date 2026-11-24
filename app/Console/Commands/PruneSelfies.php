<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Support\AttendanceRules;
use App\Support\AttendanceSelfie;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Delete clock-in photos once they are old enough.
 *
 * A photo of a person is kept only as long as there is a reason to look at
 * it. The day itself stays on record - clock in, clock out, position, device -
 * and says its photo was deleted, so an old day never reads as a photo that
 * was not taken.
 *
 * Two things are swept: the photos of days older than the retention period,
 * and any file on the disk that no attendance row points at any more (the row
 * was deleted, or a write failed after the file was saved), which nothing
 * else would ever clean up.
 */
class PruneSelfies extends Command
{
    protected $signature = 'attendance:prune-selfies {--dry-run : List what would go, and delete nothing.}';

    protected $description = 'Delete clock-in photos older than the retention period';

    public function handle(): int
    {
        $disk   = Storage::disk(AttendanceSelfie::DISK);
        $days   = AttendanceSelfie::retentionDays();
        $now    = Carbon::now(AttendanceRules::TIMEZONE);
        $cutoff = $now->copy()->subDays($days)->startOfDay();
        $dry    = (bool) $this->option('dry-run');

        $this->info(($dry ? 'Would delete' : 'Deleting').' clock-in photos from before '
            .$cutoff->format('d M Y').' ('.$days.'-day rule).');

        $removed = 0;

        // Days older than the cutoff that still have a photo.
        Attendance::whereNotNull('selfie_path')
            ->whereDate('date', '<', $cutoff->toDateString())
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($disk, $dry, $now, &$removed) {

                foreach ($rows as $row) {

                    $removed++;

                    if ($dry) {
                        continue;
                    }

                    $disk->delete($row->selfie_path);

                    // The row says so, rather than going quiet: an old day
                    // must not look like a photo that was never taken.
                    $row->forceFill([
                        'selfie_path'       => null,
                        'selfie_deleted_at' => $now,
                    ])->save();
                }
            });

        // Files nothing points at any more, old enough to be sure they are not
        // a clock-in that is being written right now.
        $known = Attendance::whereNotNull('selfie_path')->pluck('selfie_path')->flip();

        $orphans = 0;

        foreach ($disk->allFiles('selfies') as $file) {

            if ($known->has($file)) {
                continue;
            }

            if (Carbon::createFromTimestamp($disk->lastModified($file))->gte($cutoff)) {
                continue;
            }

            $orphans++;

            if (! $dry) {
                $disk->delete($file);
            }
        }

        // Month folders left empty.
        if (! $dry) {
            foreach ($disk->directories('selfies') as $dir) {
                if (empty($disk->allFiles($dir))) {
                    $disk->deleteDirectory($dir);
                }
            }
        }

        $this->info($removed.' photo(s) '.($dry ? 'would be ' : '').'deleted, '
            .$orphans.' stray file(s) '.($dry ? 'would be ' : '').'removed.');

        return self::SUCCESS;
    }
}

<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_custom_password_recovery;

/**
 * Sliding-window rate limiter backed by the Moodle application cache.
 *
 * @package    local_custom_password_recovery
 * @copyright  2026 CEAD/UnB
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rate_limiter {

    /** @var int Maximum window supported (limited by the cache TTL). */
    const MAX_WINDOW = DAYSECS;

    /**
     * Registers a hit for the key if it is still under the limit.
     *
     * @param string $key Cache key (a-zA-Z0-9_ only).
     * @param int $limit Maximum hits allowed inside the window. 0 or less disables the limit.
     * @param int $window Window length in seconds.
     * @return bool True if the hit was allowed (and recorded), false if the limit was reached.
     */
    public static function hit(string $key, int $limit, int $window): bool {
        if ($limit <= 0) {
            return true;
        }
        $window = max(1, min($window, self::MAX_WINDOW));
        $now = time();

        $cache = \cache::make('local_custom_password_recovery', 'ratelimit');
        $hits = $cache->get($key);
        $hits = is_array($hits) ? $hits : [];
        $hits = array_values(array_filter($hits, function($t) use ($now, $window) {
            return (int)$t > $now - $window;
        }));

        if (count($hits) >= $limit) {
            return false;
        }
        $hits[] = $now;
        $cache->set($key, $hits);
        return true;
    }
}

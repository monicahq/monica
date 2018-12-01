/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace App\Helpers;

use PDO;
use Illuminate\Support\Facades\DB;

class DBHelper
{
    /**
     * Get the version of DB engine.
     *
     * @return string
     */
    public static function version()
    {
        try {
            return DB::connection()->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Test if db version if greater than $version param.
     *
     * @param string
     * @return bool
     */
    public static function testVersion($version)
    {
        return version_compare(static::version(), $version) >= 0;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function getTables()
    {
        return DB::table('information_schema.tables')
            ->select('table_name')
            ->where('table_schema', '=', DB::connection()->getDatabaseName())
            ->get();
    }
}

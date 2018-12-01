<?php
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


namespace App\Services\Contact\Tag;

use App\Models\Contact\Tag;
use App\Services\BaseService;

class CreateTag extends BaseService
{
    /**
     * Get the validation rules that apply to the service.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'name' => 'required|string',
        ];
    }

    /**
     * Create a tag.
     *
     * @param array $data
     * @return Tag
     */
    public function execute(array $data) : Tag
    {
        $this->validate($data);

        $array = [
            'account_id' => $data['account_id'],
            'name' => $data['name'],
            'name_slug' => str_slug($data['name']),
        ];

        return Tag::create($array);
    }
}

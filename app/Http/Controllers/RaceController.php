<?php

namespace App\Http\Controllers;
use App\Models\Race;


/**
 * @group Race
 * Operations related to races.
 * 這裡的種族就是寶可夢的意思
 * 
 * 
 * 
 */
class RaceController extends Controller
{
    /**
     * 取得寶可夢種族的列表。
     * 
     * 此API不需要驗證。
     * 此方法返回一個包含所有寶可夢種族的列表，
     * 每個種族包括其ID、名稱和照片。列表會分頁，每頁包含12個項目。
     *
     * @response 
     *         
     * {
     *"current_page": 1,
     *"data": [
     *{
     * "id": 1,
     * "name": "bulbasaur",
     * "photo": "https://raw.githubusercontent.com/PokeAPI/*sprites/master/sprites/pokemon/1.png"
     * "stock":334,
     * "price":2344
     * },
     *{
     * "id": 2,
     * "name": "ivysaur",
     * "photo": "https://raw.githubusercontent.com/PokeAPI/*sprites/master/sprites/pokemon/2.png",
     * "stock":334,
     * "price":2344
     *},
     *{
     * "id": 3,
     * "name": "venusaur",
     * "photo": "https://raw.githubusercontent.com/PokeAPI/*sprites/master/sprites/pokemon/3.png",
     * "stock":334,
     * "price":2344
     *},...
       
     *],
     *"first_page_url": "http://localhost:8000/api/races?page=1",
     *"from": 1,
     *"last_page": 85,
     *"last_page_url": "http://localhost:8000/api/races?page=85",
     *"next_page_url": "http://localhost:8000/api/races?page=2",
     *"path": "http://localhost:8000/api/races",
     *"per_page": 12,
     *"prev_page_url": null,
     *"to": 12,
     *"total": 1010
     *}

     */
    public function index()
    {
        // 選擇所有寶可夢的名稱和照片
        $pokemons = Race::select('id', 'name', 'photo')->paginate(10);

        return $pokemons;
    }

    // public function evolutionLevel(Race $race)
    // {

    //     $evolutionLevel = $race->evolution_level;
    //     return response(['id' => $race->id, 'evolution_level' => $evolutionLevel]);
    // }

    // public function skills(Race $race)
    // {
    //     $skills = $race->skills()->orderBy('id')->get();
    //     return SkillResource::collection($skills);
    // }
}

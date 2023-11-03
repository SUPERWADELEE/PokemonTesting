<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchPokemonRequest;
use App\Http\Requests\StorePokemonRequest;
use App\Http\Requests\UpdatePokemonRequest;
use App\Http\Resources\PokemonResource;
use App\Models\Nature;
use App\Models\Pokemon;
use App\Models\Race;
use App\Models\Skill;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Mockery\Expectation;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request as LaravelRequest;
use Illuminate\Support\Facades\Log;


/**
 * @group Pokemons
 * Operations related to pokemons.
 * 
 * @authenticated
 */

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Facades\JWTAuth;

class PokemonController extends Controller
{

    /**
     
     * 寶可夢列表
     *
     * @group Pokemons
     * @authenticated
     *
     * @bodyParam first_name string required The first name of the user.
     * @bodyParam last_name string required The last name of the user.
     * @bodyParam email string required The email address of the user.
     * 
    * @response {
     *     "id": 123,
     *     "name": "myBaby",
     *     "level": 50,
     *     "race_id": 25,
     *     "race": "Pikachu",
     *     "photo": "http://example.com/pikachu.jpg",
     *     "ability": "Static",
     *     "nature": "Jolly",
     *     "skills": ["Thunderbolt", "Quick Attack"],
     *     "host": "Ash Ketchum"
     *     "evolution_level":16
     * }
 
     */

    public function index()
    {
        // 透過JWT取得當前登入的用戶
        $user = auth()->user();

        $pokemons = $user->pokemons()->with(['user', 'ability', 'nature', 'race'])->get();
        return PokemonResource::collection($pokemons);
    }

    
    /**
     * 顯示指定寶可夢的詳細資訊。
     *
     * 此方法獲取指定寶可夢的詳細資訊，包括其種族、能力、性格和技能。
     *
     * @urlParam pokemon integer required 寶可夢的ID。示例：1
     *
     * @response {
     *   "id": 1,
     *   "name": "daddy",
     *   "level": 10,
     *   "race_id": 1,
     *   "race": "pikachu",
     *   "ability": "靜電",
     *   "nature": "認真",
     *   "skills": ["電擊", "鐵尾"],
     *   "host": "Ash"
     * }
     */
    public function show(Pokemon $pokemon)
    {
        $this->authorize('show', $pokemon);
        $pokemon->load(['user', 'ability', 'nature', 'race']);
        return PokemonResource::make($pokemon);
    }

    /**
     * 刪除指定的寶可夢。
     *
     * 此方法允許授權的使用者刪除他們的寶可夢。
     * 成功刪除寶可夢後，將返回成功響應。
     *
     * @urlParam pokemon integer required 寶可夢的ID。示例：1
     *
     * @response 200 {
     *   "message": "pokemon deleted successfully"
     * }
     * @response 204 {
     *   描述：無內容響應，表示成功刪除了寶可夢。
     * }
     */
    public function destroy(Pokemon $pokemon)
    {
        $this->authorize('delete', $pokemon);
        // 刪除該寶可夢
        $pokemon->delete();
        // 返回成功響應
        return response(['message' => 'pokemon deleted successfully'], 200);
        return response()->noContent();
    }



    // TODO寶可夢進化等級可以用一個evolution_id 儲存

    /**
     * 判斷指定的寶可夢是否可以進化。
     *
     * 此方法首先檢查當前用戶是否有權進行進化操作，
     * 然後檢查寶可夢是否達到其種族所需的進化等級。
     * 如果寶可夢已達到進化等級，則更新其種族ID以反映其新的進化形態。
     *
     * @authenticated
     *
     * @urlParam pokemon integer required 寶可夢的ID。範例：1
     * 
     * @response 200 {
     *   "message": "寶可夢已成功進化"
     * }
     * @response 200 {
     *   "message": "寶可夢未達進化條件"
     * }
     * @response 200 {
     *   "message": "寶可夢已是最終形態"
     * }
     */
    public function evolution(Pokemon $pokemon)
    {
        $this->authorize('evolution', $pokemon);
        // 拿到寶可夢進化等級
        $pokemon->load('race');
        // $pokemon = Pokemon::with('race')->find($id);
        // 取得這隻寶可夢的進化等級

        $evolutionLevel = $pokemon->race->evolution_level;

        try {
            if (!$evolutionLevel) {
                throw new Exception("寶可夢已是最終形態");
            }

            // 因為id有照順序排所以通常id+1就會是他進化的種族的id
            if ($pokemon->level > $evolutionLevel) {
                $pokemon->update(['race_id' => $pokemon->race_id + 1]);
                return response(200);
            }

            throw new Exception("寶可夢未達進化條件");
        } catch (Exception $e) {
            return response(['message' => $e->getMessage()], 200);
        }
    }


    public function search(SearchPokemonRequest $request)
    {

        //    TODO命名規則要注意  不要用＿
        $query = Pokemon::query();
        $name = $request->input('name');
        $nature_id = $request->input('nature_id');
        $ability_id = $request->input('ability_id');
        $level = $request->input('level');
        $race_id = $request->input('race_id');

        // TODO$query->when($request->input('name'), function($pokemons, $name){
        //         $pokemons->where('name', 'LIKE', "%$name%");
        // });
        // 如果有提供名稱，則增加名稱的搜尋條件
        if ($name) {
            $query->where('name', 'LIKE', "%$name%");
        }

        // 如果有提供性格 ID，則增加性格的搜尋條件
        if ($nature_id) {
            $query->where('nature_id', $nature_id);
        }

        if ($ability_id) {
            $query->where('ability_id', $ability_id);
        }

        if ($level) {
            $query->where('level', $level);
        }

        if ($race_id) {
            $query->where('race_id', $race_id);
        }

        // $pokemons =  $query->with(['race', 'ability', 'nature'])
        //     ->Where('name', 'LIKE', '%' . $name . '%')
        //     ->Where('nature_id', $nature_id)
        //     ->get();
        $pokemons = $query->get();
        return PokemonResource::collection($pokemons);
    }
}

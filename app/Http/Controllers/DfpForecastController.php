<?php

namespace App\Http\Controllers;

use App\DFPWrapper;
use Google\AdsApi\Common\OAuth2TokenBuilder;
use Google\AdsApi\Dfp\DfpServices;
use Google\AdsApi\Dfp\DfpSessionBuilder;
use Illuminate\Http\Request;

class DfpForecastController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function new()
    {
        return view('dfpForecast');
    }

    public function create(Request $request)
    {
        $timestart = microtime(true);
        $validatedData = $request->validate([
            'startdate' => 'required|date',
            'enddate' => 'required|date',
        ]);

        $dfp = self::getForecaster();

        $dfp->setDateRange(request('startdate'), request('enddate'));

        $domains = request('domain') ?: null;

        $data = $dfp->forecast($domains);

        $time = microtime(true) - $timestart;

        return view('dfpForecast', compact('data', 'time'));
    }

    public static function getForecaster()
    {
        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->fromFile("../adsapi_php.ini")
            ->build();
        $session = (new DfpSessionBuilder())
            ->fromFile("../adsapi_php.ini")
            ->withOAuth2Credential($oAuth2Credential)
            ->build();

        return new DFPWrapper(new DfpServices(), $session);
    }

    public static function domainCheckboxSelected($domain)
    {
        echo (isset($_REQUEST['domain']) && in_array($domain, request('domain')) || !isset($_REQUEST['domain']) ? 'checked' : '');
    }

}

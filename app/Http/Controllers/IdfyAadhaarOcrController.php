<?php

namespace App\Http\Controllers;

use App\idfyAadharOcr;
use App\Jobs\IdfyAadharOcrJob;
use App\LazyPayUsers;
use Illuminate\Http\Request;

class IdfyAadhaarOcrController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

    }

    public function getUserInfo(){

        $lazyPayUsers = new LazyPayUsers();
        $data = $lazyPayUsers->get(['*']);

        $this->dispatch(new IdfyAadharOcrJob($data));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\idfyAadharOcr  $idfyAadharOcr
     * @return \Illuminate\Http\Response
     */
    public function show(idfyAadharOcr $idfyAadharOcr)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\idfyAadharOcr  $idfyAadharOcr
     * @return \Illuminate\Http\Response
     */
    public function edit(idfyAadharOcr $idfyAadharOcr)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\idfyAadharOcr  $idfyAadharOcr
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, idfyAadharOcr $idfyAadharOcr)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\idfyAadharOcr  $idfyAadharOcr
     * @return \Illuminate\Http\Response
     */
    public function destroy(idfyAadharOcr $idfyAadharOcr)
    {
        //
    }
}

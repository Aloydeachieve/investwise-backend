<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Http\Requests\CreatePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use Illuminate\Http\Request;

class AdminPlanController extends Controller
{
    public function index()
    {
        $plans = Plan::all();
        return response()->json([
            'success' => true,
            'data' => $plans
        ]);
    }

    public function store(CreatePlanRequest $request)
    {
        $plan = Plan::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully',
            'data' => $plan
        ], 201);
    }

    public function show($id)
    {
        $plan = Plan::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $plan
        ]);
    }

    public function update(UpdatePlanRequest $request, $id)
    {
        $plan = Plan::findOrFail($id);
        $plan->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Plan updated successfully',
            'data' => $plan
        ]);
    }

    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan deleted successfully'
        ]);
    }
}

<?php namespace Skvn\Crud\Middleware;

use Closure;
use Skvn\Crud\Models\CrudModel;

class ModelAcl {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $modelName = $request->route()->parameter('model');
        if (!empty($modelName))
        {
            $modelInst = CrudModel::createInstance($modelName);
            if (!$modelInst->checkAcl())
            {
                if ($request->ajax() || $request->wantsJson()) {
                    return response('Unauthorized.', 401);
                } else {
                    return redirect()->guest('login');
                }
            }
        }
        return $next($request);
    }

}
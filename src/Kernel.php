<?php
/**
 * MIT License
 *
 * Copyright (c) 2023-Present Kevin Traini
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

declare(strict_types=1);

namespace Marmotte\Core;

use Marmotte\Brick\Bricks\BrickLoader;
use Marmotte\Brick\Bricks\BrickManager;
use Marmotte\Brick\Cache\CacheManager;
use Marmotte\Brick\Mode;
use Marmotte\Http\Request\ServerRequest;
use Marmotte\Router\Router\Router;
use Marmotte\Teng\Engine;
use RuntimeException;
use Throwable;

final class Kernel
{
    public function run(string $project_root, string $config_dir, string $cache_dir, Mode $mode): void
    {
        try {
            $brick_manager = new BrickManager();
            $brick_loader  = new BrickLoader(
                $brick_manager,
                new CacheManager($cache_dir, $mode)
            );
            $brick_loader->loadFromCache();

            $service_manager = $brick_manager->initialize($project_root, $config_dir);

            $request = $service_manager->getService(ServerRequest::class);
            $router  = $service_manager->getService(Router::class);
            $teng    = $service_manager->getService(Engine::class);
            if ($request === null || $router === null || $teng === null) {
                throw new RuntimeException("Fail to load Services");
            }

            $teng->addValue('site', [
                'method' => $request->getMethod(),
                'uri'    => (string) $request->getUri()
            ]);

            $router->route($request->getUri()->getPath(), $request->getMethod());
        } catch (Throwable $e) {
            if ($mode !== Mode::PROD) {
                echo $e->getMessage() . "\n" . $e->getTraceAsString();
            }
        }
    }
}

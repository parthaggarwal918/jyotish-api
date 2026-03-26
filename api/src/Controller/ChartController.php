<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Jyotish\Lib;
use Jyotish\DrawChart\Draw;

class ChartController extends AbstractController
{
    private Lib $lib;

    public function __construct()
    {
        $this->lib = new Lib();
    }

    /**
     * @Route("/api/chart/svg", name="chart_svg", methods={"GET"})
     */
    public function svg(Request $request): Response
    {
        $required = ['latitude', 'longitude', 'year', 'month', 'day', 'hour', 'min', 'sec', 'time_zone'];
        $missing  = [];
        foreach ($required as $p) {
            if (!$request->query->has($p)) {
                $missing[] = $p;
            }
        }
        if (!empty($missing)) {
            return new Response(
                json_encode(['error' => 'Missing parameters', 'missing' => $missing]),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $style = strtolower($request->query->get('style', 'north'));
        $varga = strtoupper($request->query->get('varga', 'D1'));
        $size  = max(300, min(800, (int) $request->query->get('size', 500)));

        $params = [
            'latitude'  => $request->query->get('latitude'),
            'longitude' => $request->query->get('longitude'),
            'year'      => $request->query->get('year'),
            'month'     => $request->query->get('month'),
            'day'       => $request->query->get('day'),
            'hour'      => $request->query->get('hour'),
            'min'       => $request->query->get('min'),
            'sec'       => $request->query->get('sec'),
            'time_zone' => $request->query->get('time_zone'),
            'dst_hour'  => $request->query->get('dst_hour', 0),
            'dst_min'   => $request->query->get('dst_min',  0),
            'varga'     => [$varga],
        ];

        try {
            $data = $this->lib->buildData($params);

            $draw = new Draw($size, $size, Draw::RENDERER_SVG);
            $draw->drawChakra($data, 0, 0, [
                'chakraStyle'    => $style,
                'chakraVarga'    => $varga,
                'chakraSize'     => $size,
                'labelRashiShow' => true,
                'offsetBorder'   => 6,
            ]);

            return new Response($draw->getContent(), 200, [
                'Content-Type'  => 'image/svg+xml',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        } catch (\Exception $e) {
            return new Response(
                json_encode(['error' => $e->getMessage()]),
                500,
                ['Content-Type' => 'application/json']
            );
        }
    }
}

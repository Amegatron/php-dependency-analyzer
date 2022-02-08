<?php

declare(strict_types=1);

namespace PhpDep\Filesystem;

class DirectoryListExpander
{
    /**
     * @return array<string>
     */
    public function expandDirectoryPattern(string $directoryPattern): array
    {
        // TODO: implement
        throw new \Exception("Not implemented yet");

        $result = [];

        return $this->expandInternal($directoryPattern);
    }

    protected function expandInternal(string $directoryPattern, int &$pos = 0): array
    {
        $currentSegment = '';
        $currentSegments = [];

        while ($pos < strlen($directoryPattern)) {
            $char = $directoryPattern[$pos];
            $pos++;

            if ($char === ' ') {
                continue;
            } elseif ($char === '{') {
                if (empty($currentSegments)) {
                    $currentSegments = [$currentSegment];
                } else {
                    $currentSegments = array_map(
                        fn(string $item) => $item . $currentSegment,
                        $currentSegments,
                    );
                }
                $currentSegment = '';
                $subSegments = $this->expandInternal($directoryPattern, $pos);
                $newSegments = [];

                foreach ($subSegments as $subSegment) {
                    foreach ($currentSegments as $segment) {
                        $newSegments[] = $segment . $subSegment;
                    }
                }

                $currentSegments = $newSegments;
            } elseif ($char === '}') {
                $currentSegments[] = $currentSegment;

                return $currentSegments;
            } elseif ($char === ',') {
                if (empty($currentSegments)) {
                    $currentSegments = [$currentSegment];
                } else {
                    $currentSegments = array_map(
                        fn(string $item) => $item . $currentSegment,
                        $currentSegments,
                    );
                }

                $currentSegment = '';
            } else {
                $currentSegment .= $char;
            }
        }

        return $currentSegments;
    }

    protected function _expandDirectoryPatternInternal(string $directoryPattern): array
    {
//        $result = [];
        $currentStack = [];
        $resultStack = [];
        $pathSegment = '';
        $depth = 0;

        for ($i = 0; $i < strlen($directoryPattern); $i++) {
            $char = $directoryPattern[$i];

            if ($char === ' ') {
                continue;
            } elseif ($char === '{' || $char === '}') {
                if (!isset($currentStack[$depth])) {
                    $currentStack[$depth] = [
                        'items' => [$pathSegment],
                        'children' => [],
                    ];
                } else {
                    $currentStack[$depth]['items'] = array_map(
                        static function (string $item) use ($pathSegment) {
                            return $item . $pathSegment;
                        },
                        $currentStack[$depth]['items'],
                    );
                }

                if ($char === '}') {

                }

                $depth++;
                $pathSegment = '';
            } elseif ($char === ',') {
                $currentStack[$depth]['children'][] = [
                    'items' => [$pathSegment],
                    'children' => [],
                ];
                $pathSegment = '';
            } else {
                $pathSegment .= $char;
            }
        }

        return $currentStack;
    }
}

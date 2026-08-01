<?php defined('BX_DOL') or die('hack attempt');
/**
 * Copyright (c) UNA, Inc - https://una.io
 * MIT License - https://opensource.org/licenses/MIT
 *
 * @defgroup    UnaCore UNA Core
 * @{
 */

use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\ToolProperty;
use NeuronAI\Tools\ArrayProperty;
use NeuronAI\Tools\ObjectProperty;

class BxDolAIToolContentAdd extends BxDolAITool
{
    public function __construct()
    {
        parent::__construct(
            'content_add',
            'Use this tool to add content to content modules. Always use "content_structure" tool to get knowledge about content modules fields. Available modules: ' . $this->getContentModules() . '.',
        );
    }

    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'module',
                type: PropertyType::STRING,
                description: 'The module name to perform content changes on. Use "system" for site accounts. ',
                required: true
            ),
            new ArrayProperty(
                name: 'data',
                description: 'Key-value pairs of data fields to add, where keys match the field names. Example: {"title": "Hello song title", "text": "Hello song text goes here."}.',
                required: true,
                items: new ObjectProperty(
                    name: 'parameter',
                    properties: [
                        new ToolProperty('name', PropertyType::STRING, 'Parameter name', true),
                        new ToolProperty('value', PropertyType::STRING, 'Parameter value', true),
                    ]
                )
            ),
            new ToolProperty(
                name: 'profile_id',
                type: PropertyType::INTEGER,
                description: 'Author profile ID.',
                required: true
            )
        ];
    }

    public function __invoke(string $module, array $data, int $profile_id): array
    {
        if ($profile_id) {
            $GLOBALS['glForceCurrentProfileId'] = $profile_id;
        }

        $a = bx_srv('system', 'add', [$module, $this->convertArrayToKeyValue($data)], 'TemplServiceContent');

        $GLOBALS['glForceCurrentProfileId'] = 0;

        return $a;
    }
}

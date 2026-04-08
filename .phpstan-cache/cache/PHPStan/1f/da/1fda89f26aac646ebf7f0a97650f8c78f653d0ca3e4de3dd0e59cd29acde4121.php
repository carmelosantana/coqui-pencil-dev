<?php declare(strict_types = 1);

// odsl-/Users/carmelo/Projects/CoquiBot/Toolkits/coqui-toolkit-pencil-dev/src/Schema/PenSchema.php-PHPStan\BetterReflection\Reflection\ReflectionClass-CoquiBot\Toolkits\PencilDev\Schema\PenSchema
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.65.0.9-8.4.18-937353daea746f336d70885fa74d2c9252e6b2e14954118c95372129482281bd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'filename' => '/Users/carmelo/Projects/CoquiBot/Toolkits/coqui-toolkit-pencil-dev/src/Schema/PenSchema.php',
      ),
    ),
    'namespace' => 'CoquiBot\\Toolkits\\PencilDev\\Schema',
    'name' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
    'shortName' => 'PenSchema',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Element type definitions and validation rules derived from Pencil\'s TypeScript schema.
 *
 * @see https://docs.pencil.dev/for-developers/the-pen-format
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 239,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'ELEMENT_TYPES' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'ELEMENT_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'rectangle\', \'ellipse\', \'line\', \'polygon\', \'path\', \'text\', \'frame\', \'group\', \'ref\', \'icon_font\', \'note\', \'prompt\', \'context\']',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 29,
            'startTokenPos' => 35,
            'startFilePos' => 355,
            'endTokenPos' => 76,
            'endFilePos' => 591,
          ),
        ),
        'docComment' => '/** All known element types in the .pen format. */',
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'CONTAINER_TYPES' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'CONTAINER_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'frame\', \'group\']',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 89,
            'startFilePos' => 682,
            'endTokenPos' => 94,
            'endFilePos' => 699,
          ),
        ),
        'docComment' => '/** Element types that can contain children. */',
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 54,
      ),
      'GRAPHIC_TYPES' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'GRAPHIC_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'rectangle\', \'ellipse\', \'line\', \'polygon\', \'path\', \'text\', \'frame\']',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 43,
            'startTokenPos' => 107,
            'startFilePos' => 803,
            'endTokenPos' => 130,
            'endFilePos' => 933,
          ),
        ),
        'docComment' => '/** Element types that support fill/stroke/effect graphics. */',
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'LAYOUT_DIRECTIONS' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'LAYOUT_DIRECTIONS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'none\', \'vertical\', \'horizontal\']',
          'attributes' => 
          array (
            'startLine' => 46,
            'endLine' => 46,
            'startTokenPos' => 143,
            'startFilePos' => 1010,
            'endTokenPos' => 151,
            'endFilePos' => 1043,
          ),
        ),
        'docComment' => '/** Valid layout directions. */',
        'attributes' => 
        array (
        ),
        'startLine' => 46,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 72,
      ),
      'JUSTIFY_CONTENT' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'JUSTIFY_CONTENT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'start\', \'center\', \'end\', \'space_between\', \'space_around\']',
          'attributes' => 
          array (
            'startLine' => 49,
            'endLine' => 49,
            'startTokenPos' => 164,
            'startFilePos' => 1123,
            'endTokenPos' => 178,
            'endFilePos' => 1181,
          ),
        ),
        'docComment' => '/** Valid justify-content values. */',
        'attributes' => 
        array (
        ),
        'startLine' => 49,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 95,
      ),
      'ALIGN_ITEMS' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'ALIGN_ITEMS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'start\', \'center\', \'end\']',
          'attributes' => 
          array (
            'startLine' => 52,
            'endLine' => 52,
            'startTokenPos' => 191,
            'startFilePos' => 1253,
            'endTokenPos' => 199,
            'endFilePos' => 1278,
          ),
        ),
        'docComment' => '/** Valid align-items values. */',
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 58,
      ),
      'TEXT_ALIGN' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'TEXT_ALIGN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'left\', \'center\', \'right\', \'justify\']',
          'attributes' => 
          array (
            'startLine' => 55,
            'endLine' => 55,
            'startTokenPos' => 212,
            'startFilePos' => 1352,
            'endTokenPos' => 223,
            'endFilePos' => 1389,
          ),
        ),
        'docComment' => '/** Valid text alignment values. */',
        'attributes' => 
        array (
        ),
        'startLine' => 55,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 69,
      ),
      'TEXT_ALIGN_VERTICAL' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'TEXT_ALIGN_VERTICAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'top\', \'middle\', \'bottom\']',
          'attributes' => 
          array (
            'startLine' => 58,
            'endLine' => 58,
            'startTokenPos' => 236,
            'startFilePos' => 1481,
            'endTokenPos' => 244,
            'endFilePos' => 1507,
          ),
        ),
        'docComment' => '/** Valid text vertical alignment values. */',
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 67,
      ),
      'TEXT_GROWTH' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'TEXT_GROWTH',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'auto\', \'fixed-width\', \'fixed-width-height\']',
          'attributes' => 
          array (
            'startLine' => 61,
            'endLine' => 61,
            'startTokenPos' => 257,
            'startFilePos' => 1578,
            'endTokenPos' => 265,
            'endFilePos' => 1622,
          ),
        ),
        'docComment' => '/** Valid text growth modes. */',
        'attributes' => 
        array (
        ),
        'startLine' => 61,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 77,
      ),
      'STROKE_ALIGN' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'STROKE_ALIGN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'inside\', \'center\', \'outside\']',
          'attributes' => 
          array (
            'startLine' => 64,
            'endLine' => 64,
            'startTokenPos' => 278,
            'startFilePos' => 1700,
            'endTokenPos' => 286,
            'endFilePos' => 1730,
          ),
        ),
        'docComment' => '/** Valid stroke alignment values. */',
        'attributes' => 
        array (
        ),
        'startLine' => 64,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 64,
      ),
      'FILL_TYPES' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'FILL_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'color\', \'gradient\', \'image\', \'mesh_gradient\']',
          'attributes' => 
          array (
            'startLine' => 67,
            'endLine' => 67,
            'startTokenPos' => 299,
            'startFilePos' => 1793,
            'endTokenPos' => 310,
            'endFilePos' => 1839,
          ),
        ),
        'docComment' => '/** Valid fill types. */',
        'attributes' => 
        array (
        ),
        'startLine' => 67,
        'endLine' => 67,
        'startColumn' => 5,
        'endColumn' => 78,
      ),
      'GRADIENT_TYPES' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'GRADIENT_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'linear\', \'radial\', \'angular\']',
          'attributes' => 
          array (
            'startLine' => 70,
            'endLine' => 70,
            'startTokenPos' => 323,
            'startFilePos' => 1910,
            'endTokenPos' => 331,
            'endFilePos' => 1940,
          ),
        ),
        'docComment' => '/** Valid gradient types. */',
        'attributes' => 
        array (
        ),
        'startLine' => 70,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 66,
      ),
      'IMAGE_MODES' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'IMAGE_MODES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'stretch\', \'fill\', \'fit\']',
          'attributes' => 
          array (
            'startLine' => 73,
            'endLine' => 73,
            'startTokenPos' => 344,
            'startFilePos' => 2010,
            'endTokenPos' => 352,
            'endFilePos' => 2035,
          ),
        ),
        'docComment' => '/** Valid image fill modes. */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 58,
      ),
      'BLEND_MODES' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'BLEND_MODES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'normal\', \'darken\', \'multiply\', \'linearBurn\', \'colorBurn\', \'light\', \'screen\', \'linearDodge\', \'colorDodge\', \'overlay\', \'softLight\', \'hardLight\', \'difference\', \'exclusion\', \'hue\', \'saturation\', \'color\', \'luminosity\']',
          'attributes' => 
          array (
            'startLine' => 76,
            'endLine' => 81,
            'startTokenPos' => 365,
            'startFilePos' => 2100,
            'endTokenPos' => 421,
            'endFilePos' => 2353,
          ),
        ),
        'docComment' => '/** Valid blend modes. */',
        'attributes' => 
        array (
        ),
        'startLine' => 76,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'EFFECT_TYPES' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'EFFECT_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'blur\', \'background_blur\', \'shadow\']',
          'attributes' => 
          array (
            'startLine' => 84,
            'endLine' => 84,
            'startTokenPos' => 434,
            'startFilePos' => 2420,
            'endTokenPos' => 442,
            'endFilePos' => 2456,
          ),
        ),
        'docComment' => '/** Valid effect types. */',
        'attributes' => 
        array (
        ),
        'startLine' => 84,
        'endLine' => 84,
        'startColumn' => 5,
        'endColumn' => 70,
      ),
      'SHADOW_TYPES' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'SHADOW_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'inner\', \'outer\']',
          'attributes' => 
          array (
            'startLine' => 87,
            'endLine' => 87,
            'startTokenPos' => 455,
            'startFilePos' => 2523,
            'endTokenPos' => 460,
            'endFilePos' => 2540,
          ),
        ),
        'docComment' => '/** Valid shadow types. */',
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 5,
        'endColumn' => 51,
      ),
      'VARIABLE_TYPES' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'VARIABLE_TYPES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'color\', \'number\', \'string\', \'boolean\']',
          'attributes' => 
          array (
            'startLine' => 90,
            'endLine' => 90,
            'startTokenPos' => 473,
            'startFilePos' => 2625,
            'endTokenPos' => 484,
            'endFilePos' => 2664,
          ),
        ),
        'docComment' => '/** Variable types supported by Pencil. */',
        'attributes' => 
        array (
        ),
        'startLine' => 90,
        'endLine' => 90,
        'startColumn' => 5,
        'endColumn' => 75,
      ),
      'ICON_FONTS' => 
      array (
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'name' => 'ICON_FONTS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'lucide\', \'feather\', \'Material Symbols Outlined\', \'Material Symbols Rounded\', \'Material Symbols Sharp\', \'phosphor\']',
          'attributes' => 
          array (
            'startLine' => 93,
            'endLine' => 100,
            'startTokenPos' => 497,
            'startFilePos' => 2738,
            'endTokenPos' => 517,
            'endFilePos' => 2908,
          ),
        ),
        'docComment' => '/** Built-in icon font families. */',
        'attributes' => 
        array (
        ),
        'startLine' => 93,
        'endLine' => 100,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'requiredFields' => 
      array (
        'name' => 'requiredFields',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Required fields per element type.
 *
 * @return array<string, list<string>>
 */',
        'startLine' => 107,
        'endLine' => 124,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'CoquiBot\\Toolkits\\PencilDev\\Schema',
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'currentClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'aliasName' => NULL,
      ),
      'validateElement' => 
      array (
        'name' => 'validateElement',
        'parameters' => 
        array (
          'element' => 
          array (
            'name' => 'element',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 44,
            'endColumn' => 57,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Validate an element against schema rules.
 *
 * @param array<string, mixed> $element
 * @return list<string> Validation errors (empty if valid).
 */',
        'startLine' => 132,
        'endLine' => 178,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'CoquiBot\\Toolkits\\PencilDev\\Schema',
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'currentClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'aliasName' => NULL,
      ),
      'isVariableReference' => 
      array (
        'name' => 'isVariableReference',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 183,
            'endLine' => 183,
            'startColumn' => 48,
            'endColumn' => 60,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Check if a string value is a variable reference (starts with $).
 */',
        'startLine' => 183,
        'endLine' => 186,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'CoquiBot\\Toolkits\\PencilDev\\Schema',
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'currentClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'aliasName' => NULL,
      ),
      'extractVariableName' => 
      array (
        'name' => 'extractVariableName',
        'parameters' => 
        array (
          'reference' => 
          array (
            'name' => 'reference',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 191,
            'endLine' => 191,
            'startColumn' => 48,
            'endColumn' => 64,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Extract the variable name from a reference string.
 */',
        'startLine' => 191,
        'endLine' => 194,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'CoquiBot\\Toolkits\\PencilDev\\Schema',
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'currentClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'aliasName' => NULL,
      ),
      'isValidColor' => 
      array (
        'name' => 'isValidColor',
        'parameters' => 
        array (
          'color' => 
          array (
            'name' => 'color',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 199,
            'endLine' => 199,
            'startColumn' => 41,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Check if a color string is valid (hex format).
 */',
        'startLine' => 199,
        'endLine' => 206,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'CoquiBot\\Toolkits\\PencilDev\\Schema',
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'currentClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'aliasName' => NULL,
      ),
      'defaultElement' => 
      array (
        'name' => 'defaultElement',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 213,
            'endLine' => 213,
            'startColumn' => 43,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'id' => 
          array (
            'name' => 'id',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 213,
            'endLine' => 213,
            'startColumn' => 57,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build a default element structure for a given type.
 *
 * @return array<string, mixed>
 */',
        'startLine' => 213,
        'endLine' => 238,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'CoquiBot\\Toolkits\\PencilDev\\Schema',
        'declaringClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'implementingClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'currentClassName' => 'CoquiBot\\Toolkits\\PencilDev\\Schema\\PenSchema',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));
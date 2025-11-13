<?php
namespace App\GraphQL\Type;

use ApiPlatform\GraphQl\Type\Definition\TypeInterface;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type as GqlType;

final class RegisterUserProfileInputType extends InputObjectType implements TypeInterface
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'RegisterUserProfileInput',
            'fields' => [
                'firstName'   => GqlType::nonNull(GqlType::string()),
                'lastName'    => GqlType::nonNull(GqlType::string()),
                'companyRole' => GqlType::string(),
                'displayName' => GqlType::string(),
                'description' => GqlType::string(),
                'imageId'     => GqlType::id(),
            ],
        ]);
    }

    // Api Platform usa este nombre para el registro
    public function getName(): string
    {
        return 'RegisterUserProfileInput';
    }
}

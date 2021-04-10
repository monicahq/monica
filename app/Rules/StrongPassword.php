<?php

namespace App\Rules;

use Safe;
use ZxcvbnPhp\Zxcvbn;
use Illuminate\Contracts\Validation\Rule;

class StrongPassword implements Rule
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var null|string
     */
    private $warning;

    /**
     * @param array $data
     */
    public function __construct(?array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $strength = app(Zxcvbn::class)->passwordStrength($value, $this->data);

        if ($strength['feedback']['warning']) {
            $this->warning = $strength['feedback']['warning'];
        }

        return $strength['score'] >= config('zxcvbn.password_strength_threshold');
    }

    /**
     * @return string
     */
    public function message()
    {
        $code = config(Safe\sprintf('zxcvbn.translations_map.%s', $this->warning));
        $key = $code ? Safe\sprintf('validation.insecure_password_%s', $code) : 'validation.insecure_password';

        return (string) trans($key);
    }
}

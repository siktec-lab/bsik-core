<?php

namespace Siktec\Bsik\Objects;


/** 
 * Password
 * 
 * This class is used to validate and hash passwords.
 * 
 * @package Siktec\Bsik\Objects
 */
class Password {

    protected string $hash = "";
    protected string $salt = "";
    protected string $method = "sha512";
    
    /** @var array Holds an array of any errors encountered whilst validating the password. */
    protected array $errors = [];
    
    /** use dictionary: */
    protected bool $use_dic = false;
    
    /** @var int The minimum number of characters that the password must be. */
    protected int $min_length = 8;

    /**  @var int The maximum number of characters that the password must be. */
    protected int $max_length = 50;

    /** @var int The minimum number of numbers that the password should contain. */
    protected int $min_numbers = 1;
    
    /** @var int The minimum number of letters that the password should contain. */
    protected int $min_letters = 1;

    /** @var int The minimum number of lower case letters that the password should contain. */
    protected int $min_lowercase = 1;

    /** @var int The minimum number of upper case letters that the password should contain. */
    protected int $min_uppercase = 1;

    /** @var int The minimum number of symbols that the password should contain. */
    protected int $min_symbols = 1;

    /** @var int The maximum number of symbols that the password should contain. */
    protected int $max_symbols = 20;

    /** @var array The symbols that are allowed to be in the password. */
    protected array $allowed_symbols;

    /** @var int The score of the password. */
    protected int $score = 100;
  
    /** Constructor */
    public function __construct(
        ?string $salt            = null,
        ?string $method          = null,
        ?int $min_length         = null,
        ?int $max_length         = null,
        ?int $min_numbers        = null,
        ?int $min_letters        = null,
        ?int $min_lowercase      = null,
        ?int $min_uppercase      = null,
        ?int $min_symbols        = null,
        ?int $max_symbols        = null,
        ?array $allowed_symbols  = null,
        bool $dictionary         = false
    ) {
        // Override defaults.
        $this->use_dic = $dictionary;
        if (!is_null($salt))    $this->salt = $salt;
        if (!is_null($method))  $this->method = $method;
        $set = [];
        if (!is_null($min_length))      $set["min_length"]      = $min_length;
        if (!is_null($max_length))      $set["max_length"]      = $max_length;
        if (!is_null($min_numbers))     $set["min_numbers"]     = $min_numbers;
        if (!is_null($min_letters))     $set["min_letters"]     = $min_letters;
        if (!is_null($min_lowercase))   $set["min_lowercase"]   = $min_lowercase;
        if (!is_null($min_uppercase))   $set["min_uppercase"]   = $min_uppercase;
        if (!is_null($min_symbols))     $set["min_symbols"]     = $min_symbols;
        if (!is_null($max_symbols))     $set["max_symbols"]     = $max_symbols;
        if (!is_null($allowed_symbols)) {
            $set["allowed_symbols"] = $allowed_symbols;
        } else {
            $this->allowed_symbols = str_split("~`!@#$%^&*()-_=+|[]{}?><.,:;");
        }
        //Set the options:
        if (!empty($set))
            $this->set_options($set);
    }
    
    /**
     * encrypt
     * hashes a string with the platform encryption scheme
     * @param  string $raw
     * @return string|bool -> the hash or FALSE on failure.
     */
    public function encrypt(string $raw) : string|bool {
        return openssl_digest($this->salt.$raw.$this->salt, $this->method);
    }
    
    /**
     * set_hash
     * set a hash - this is usefull for loading passwords from the db.
     * @param  string $hash
     * @return void
     */
    public function set_hash(string $hash) : void {
        $this->hash = trim($hash);
    }
    public function get_hash() : string {
        return $this->hash;
    }

    /**
     * set_password
     * set a passwords from raw string - the password will be encrypted salted and saved.
     * @param  string   $raw
     * @param  bool     $validate
     * @return bool
     */
    public function set_password(string $raw, bool $validate = true) : bool {
        //Validate first:
        if ($validate && !$this->validate_password($raw))
            return false;
        //encrypt and save:
        $this->hash = $this->encrypt($raw) ?: "";
        return !empty($this->hash);
    }
    
    /**
     * compare
     * compare a password to currently saved hash.
     * @param  mixed $raw
     * @return bool
     */
    public function compare(string $raw) : bool {
        return      !empty($this->hash) 
                &&  !empty($raw) 
                &&  $this->hash === $this->encrypt($raw);
    }

    /**
     * Validate the password to the defined parameters. If a parameters is not
     * set at runtime then a default value is used.
     *
     * @param string $password The password.
     *
     * @return bool True if password valid, otherwise false.
     */
    public function validate_password(string $password) : bool {
        // Make sure that parameters don't overlap in such a way as to make
        // validation impossible.
        $this->_sanitize_inputs();

        $this->errors = [];

        // Check password minimum length, return at this step.
        if (strlen($password) < $this->min_length) {
            $this->errors[] = 'Password must be ' . $this->min_length . ' characters long, current password is too short at ' . strlen($password) . ' characters.';
            return false;
        }
        // Check password maximum length, return at this step.
        if (strlen($password) > $this->max_length) {
            $this->errors[] = 'Password must be ' . $this->min_length . ' characters long, current password is too long at ' . strlen($password) . ' characters.';
            return false;
        }
        // Check the number of numbers in the password.
        if (strlen(preg_replace('/([^0-9]*)/', '', $password)) < $this->min_numbers) {
            $this->errors[] = 'Not enough numbers in password, a minimum of ' . $this->min_numbers . ' required.';
        }
        // Check the number of letters in the password
        if (strlen(preg_replace('/([^a-zA-Z]*)/', '', $password)) < $this->min_letters) {
            $this->errors[] = 'Not enough letters in password, a minimum of ' . $this->min_letters . ' required.';
        }
        // Check the number of lower case letters in the password
        if (strlen(preg_replace('/([^a-z]*)/', '', $password)) < $this->min_lowercase && $this->min_lowercase != 0) {
            $this->errors[] = 'Not enough lower case letters in password, a minimum of ' . $this->min_lowercase . ' required.';
        }
        // Check the number of upper case letters in the password
        if (strlen(preg_replace('/([^A-Z]*)/', '', $password)) < $this->min_uppercase && $this->min_uppercase != 0) {
            $this->errors[] = 'Not enough upper case letters in password, a minimum of ' . $this->min_uppercase . ' required.';
        }
        // Check the minimum number of symbols in the password.
        if (strlen(preg_replace('/([a-zA-Z0-9]*)/', '', $password)) < $this->min_symbols && $this->max_symbols != 0) {
            $this->errors[] = 'Not enough symbols in password, a minimum of ' . $this->min_symbols . ' required.';
        }
        // Check the maximum number of symbols in the password.
        if (strlen(preg_replace('/([a-zA-Z0-9]*)/', '', $password)) > $this->max_symbols) {
            if ($this->max_symbols == 0) {
                $this->errors[] = 'You are not allowed any symbols in password, please remove them.';
            } else {
                $this->errors[] = 'Too many symbols in password.';
            }
        }
        // Check that the symbols present in the password are allowed.
        if ($this->max_symbols != 0) {
            $symbols = preg_replace('/([a-zA-Z0-9]*)/', '', $password);
            for ($i = 0; $i < strlen($symbols); ++$i) {
                if (!in_array($symbols[$i], $this->allowed_symbols)) {
                    $this->errors[] = 'Non specified symbol ' . $symbols[$i] . ' used in password, please use one of ' . implode('', $this->allowed_symbols) . '.';
                }
            }
        }
        // If any errors have been encountered then return false.
        if (count($this->errors) > 0) {
            return false;
        }
        return true;
    }
    /**
     * Score the password based on the level of security. This function doesn't
     * look at the parameters set up and simply scores based on best practices.
     * The function first makes sure the password is valid as there is no
     * point in scoring a password that can't be used.
     *
     * @param string $password The password to score.
     *
     * @return int Returns an integer score of the password strength.
     */
    public function score_password(string $password) : int {    
        // Make sure password is valid.
        if (!$this->validate_password($password)) {
            return 0;
        }
        //Early out:
        if ($password == '') {
            $this->score = 0;
            return $this->score;
        }
        // Reset initial score.
        $this->score = 100;
        $password_letters = preg_replace('/([^a-zA-Z]*)/', '', $password);
        $letters = [];
        for ($i = 0; $i < strlen($password_letters); ++$i) {
            // Reduce score for duplicate letters.
            if (in_array($password_letters[$i], $letters)) {
                $this->score = $this->score - 5;
            }
            // Reduce score for duplicate letters next to each other.
            if (isset($password_letters[$i - 1]) && $password_letters[$i] == $password_letters[$i - 1]) {
                $this->score = $this->score - 10;
            }
            $letters[] = $password_letters[$i];
        }
        // Reduce score for duplicate numbers.
        $password_numbers = preg_replace('/([^0-9]*)/', '', $password);
        $numbers = [];
        for ($i = 0; $i < strlen($password_numbers); ++$i) {
            if (in_array($password_numbers[$i], $numbers)) {
                $this->score = $this->score - 5;
            }
            $numbers[] = $password_numbers[$i];
        }
        // Reduce score for no symbols.
        if (strlen(preg_replace('/([a-zA-Z0-9]*)/', '', $password)) == 0) {
            $this->score = $this->score - 10;
        }
        // Reduce score for words in dictionary used in password.
        $dictionary = dirname(__FILE__) . '/words.txt';
        if ($this->use_dic && file_exists($dictionary)) {
            $handle = fopen($dictionary, "r");
            $words = '';
            while (!feof($handle)) {
                $words .= fread($handle, 8192);
            }
            fclose($handle);
            $words = explode("\n", $words);
            foreach ($words as $word) {
                if (preg_match('/.*?' . trim($word) . '.*?/i', $password, $match)) {
                    $this->score = $this->score - 20;
                }
            }
        }
        if ($this->score < 0) {
            $this->score = 0;
        }
        // Return the score.
        return $this->score;
    }
    /**
     * Use the options set up in the class to create a random password that passes
     * validation. This uses certain practices such as not using the letter o or
     * the number 0 as these can be mixed up.
     *
     * @return string The generated password.
     */
    public function generate_password() : string {
        // Make sure that parameters don't overlap in such a way as to make
        // validation impossible.
        $this->_sanitize_inputs();
        // Initialize variable.
        $password = '';
        // Add lower case letters.
        $lower_letters = 'aeiubdghjmnpqrstvxyz';
        if ($this->min_lowercase != 0) {
            for ($i = 0; $i < $this->min_lowercase; ++$i) {
                $password .= $lower_letters[(rand() % strlen($lower_letters))];
            }
        }
        // Add upper case letters.
        $upperLetters = 'AEUBDGHJLMNPQRSTVWXYZ';
        if ($this->min_uppercase != 0) {
            for ($i = 0; $i < $this->min_uppercase; ++$i) {
                $password .= $upperLetters[(rand() % strlen($upperLetters))];
            }
        }
        // Add letters.
        if (($this->min_lowercase + $this->min_uppercase) < ($this->min_letters)) {
            $password .= $lower_letters[(rand() % strlen($lower_letters))];
        }
        // Add numbers.
        $numbers = '23456789';
        if ($this->min_numbers != 0) {
            for ($i = 0; $i < $this->min_numbers; ++$i) {
                $password .= $numbers[(rand() % strlen($numbers))];
            }
        }
        // Add symbols using the symbols array.
        if ($this->max_symbols != 0) {
            $symbols = implode('', $this->allowed_symbols);
            if ($this->min_symbols != 0 && strlen($symbols) > 0) {
                for ($i = 0; $i < $this->min_symbols; ++$i) {
                    $password .= $symbols[(rand() % strlen($symbols))];
                }
            }
        }
        // If the created password isn't quite long enough then add some lowercase
        // letters to the password string.
        if (strlen($password) < $this->min_length) {
            while (strlen($password) < $this->min_length) {
                $password .= $lower_letters[(rand() % strlen($lower_letters))];
            }
        }
        // Shuffle the characters in the password.
        $password = str_shuffle($password);
        // Return the password string.
        return $password;
    }
    /**
     * Set multiple options for the object in one go.
     *
     * @param array $options An associative array of options.
     *
     * @return void
     */
    public function set_options($options) : void {
        if (isset($options['max_length'])) {
            $this->max_length = $options['max_length'];
        }
        if (isset($options['min_length'])) {
            $this->min_length = $options['min_length'];
        }
        if (isset($options['min_numbers'])) {
            $this->min_numbers = $options['min_numbers'];
        }
        if (isset($options['min_letters'])) {
            $this->min_letters = $options['min_letters'];
        }
        if (isset($options['min_symbols'])) {
            $this->min_symbols = $options['min_symbols'];
        }
        if (isset($options['max_symbols'])) {
            $this->max_symbols = $options['max_symbols'];
        }
        if (isset($options['allowed_symbols']) && is_array($options['allowed_symbols'])) {
            $this->allowed_symbols = $options['allowed_symbols'];
        }
        if (isset($options['min_lowercase'])) {
            $this->min_lowercase = $options['min_lowercase'];
        }
        if (isset($options['min_uppercase'])) {
            $this->min_uppercase = $options['min_uppercase'];
        }
        // Make sure that parameters don't overlap in such a way as to make
        // validation impossible.
        $this->_sanitize_inputs();
    }
    /**
     * Get any errors produced through the last validation.
     *
     * @return array
     */
    public function get_errors() : array {
        return $this->errors;
    }
    /**
     * Get the maximum length of password allowed.
     *
     * @param integer $max_length The maximum length of password allowed.
     *
     * @return void
     */
    public function set_max_length($max_length) : void {
        $this->max_length = $max_length;
    }
    /**
     * The maximum character length of the password.
     *
     * @return int The maximum character length of the password.
     */
    public function get_max_length() : int {
        return $this->max_length;
    }
    /**
     * The minimum character length of the password.
     *
     * @return int The minimum character length of the password.
     */
    public function get_min_length() : int {
        return $this->min_length;
    }
    /**
     * Get the minimum length of password allowed.
     *
     * @param integer $min_length The minimum length of password allowed.
     *
     * @return void
     */
    public function set_min_length($min_length) : void {
        $this->min_length = $min_length;
    }
    /**
     * The minimum letter count in the password.
     *
     * @return int The minimum letter count in the password.
     */
    public function get_min_letters() : int {
        return $this->min_letters;
    }
    /**
     * Get the symbols allowed in password.
     *
     * @return array The allowed symbols array.
     */
    public function get_allowed_symbols() : array {
        return $this->allowed_symbols;
    }
    /**
     * An array of symbols that can be included in the password. If an array is
     * not passed to this function then it is not stored.
     * @param array|string $symbols An array of symbols that can be included in the
     *                       password. This can be a string, which will be parsed
     *                       into an array of symbols.
     * @return void
     */
    public function set_allowed_symbols(array|string $symbols) : void {
        if (is_string($symbols)) {
            $symbols = str_split($symbols);
        }
        // Filter the symbols to remove any non symbol characters.
        $symbols = array_filter($symbols, [$this, 'filter_allowed_symbols']);
        $this->allowed_symbols = array_unique($symbols);
    }
    /**
     * Callback function for set_allowed_symbols() to allow non symbol characters to be
     * filtered out of the symbols array upon insertion.
     * @param string $character The array item to inspect.
     * @return bool False if the item is a symbol, otherwise true.
     */
    protected function filter_allowed_symbols(string $character) : bool {
        if (preg_match('/[^a-zA-Z0-9 ]/', $character) == 1) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * Set the minimum number of symbols required in the password.
     * @param int $min_symbols The minimum number of symbols.
     * @return void
     */
    public function set_min_symbols(int $min_symbols) : void {
        $this->min_symbols = $min_symbols;
    }
    /**
     * Get the minimum number of symbols required in the password.
     * @return int The minimum number of symbols.
     */
    public function get_min_symbols() : int {
        return $this->min_symbols;
    }
    /**
     * Get the minimum number of upper case letters required in the password.
     * @return int The minimum number of upper case letters.
     */
    public function get_min_uppercase() : int {
        return $this->min_uppercase;
    }
    /**
     * Get the minimum number of lower case letters required in the password.
     * @return int The minimum number of lower case letters.
     */
    public function get_min_lowercase() : int {
        return $this->min_lowercase;
    }
    /**
     * Set the maximum number of symbols required in the password.
     * @param int $max_symbols The maximum number of symbols.
     * @return void
     */
    public function set_max_symbols(int $max_symbols) : void {
        $this->max_symbols = $max_symbols;
    }
    /**
     * The maximum number of symbols allowed in the password.
     * @return int The maximum number of symbols allowed in the password.
     */
    public function get_max_symbols() : int {
        return $this->max_symbols;
    }
    /**
     * Make sure that parameters don't overlap in such a way as to make
     * validation impossible. For example, if the minimum number of letters
     * numbers and symbols allowed is greater than the maximum length of the
     * password then these numbers are added together and used as the new maximum
     * password length.
     * @return void
     */
    private function _sanitize_inputs() : void {
        $min_pos_length = $this->min_numbers + $this->min_letters + $this->min_symbols;
        if ($min_pos_length > $this->min_length) {
            $this->min_length = $min_pos_length;
        }
        if ($this->min_length > $this->max_length) {
            $this->min_length = $this->max_length;
        }
        if ($this->min_symbols > $this->max_symbols) {
            $this->min_symbols = $this->max_symbols;
        }
    }

}
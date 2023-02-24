<?php

namespace Siktec\Bsik\Tools;

/**
 * Profiler class
 * a simple profiler class to measure the time of a function
 */
class Profiler {

    private float $start_time   = 0;
    private float $end_time     = 0;
    private float $total_time   = 0;
    
    /**
     * __construct
     * will start the profiler if $start is true
     * @param  bool $start if true will start the profiler
     * @return Profiler
     */
    public function __construct(bool $start = false) {
        if ($start) {
            $this->start();
        }
    }
    
    /**
     * start
     * will start the profiler and reset the total time
     * @return void
     */
    public function start() : void {
        $this->end_time = 0;
        $this->total_time = 0;
        $this->start_time = microtime(true);
    }
    
    /**
     * end
     * will end the profiler and return the total time in seconds
     * @param  bool $print if true will print the total time
     * @param  int $precision the number of decimal places to round the time to
     * @return float the total time in seconds
     */
    public function end(bool $print = false, int $precision = 5) : float {
        $this->end_time = microtime(true);
        $this->total_time = $this->end_time - $this->start_time;
        $time = $this->to_seconds($this->total_time, $precision);
        if ($print) echo $time.PHP_EOL;
        return $time;
    }
    
    /**
     * get_total_time_micro
     * will return the total time in microseconds
     * @return float
     */
    public function get_total_time_micro() : float {
        return $this->total_time;
    }
    
    /**
     * get_total_time_seconds
     * will return the total time in seconds
     * @param  int $precision the number of decimal places to round the time to
     * @return float the total time in seconds
     */
    public function get_total_time_seconds(int $precision = 5) : float {
        return $this->to_seconds($this->total_time, $precision);
    }
    
    /**
     * get_start_time
     * will return the start time in microseconds
     * @return float
     */
    public function get_start_time() : float {
        return $this->start_time;
    }
    
    /**
     * get_end_time
     * will return the end time in microseconds
     * @return float
     */
    public function get_end_time() : float {
        return $this->end_time;
    }
    
    /**
     * to_seconds
     * will convert the time from microseconds to seconds
     * @param  int|float $time the time in microseconds
     * @param  int $precision the number of decimal places to round the time to
     * @return float the time in seconds
     */
    public function to_seconds(int|float $time, int $precision = 5) : float {
        $time = $time / 1000;
        $time = round($time, $precision);
        return $time;
    }
    
    /**
     * __toString
     * will return the total time in seconds with the 'seconds' suffix
     * @return string
     */
    public function __toString() : string
    {
        return $this->get_total_time_seconds() . ' seconds';
    }
}
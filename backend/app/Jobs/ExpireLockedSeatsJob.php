<?php

namespace App\Jobs;

/**
 * @deprecated Compatibility bridge for jobs serialized before the domain move.
 */
class ExpireLockedSeatsJob extends Booking\ExpireLockedSeatsJob {}

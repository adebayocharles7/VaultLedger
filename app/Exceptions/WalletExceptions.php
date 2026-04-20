<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientFundsException extends RuntimeException {}
class FolioFrozenException extends RuntimeException {}
class StaleVersionException extends RuntimeException {}
class RemittanceCurrencyMismatchException extends RuntimeException {}
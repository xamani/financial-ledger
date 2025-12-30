<?php

declare(strict_types=1);

namespace App\OpenApi;

/**
 * @OA\Info(
 *     title="Financial Ledger API",
 *     version="1.0.0",
 *     description="Ledger-based financial reporting API."
 * )
 *
 * @OA\Server(
 *     url="/",
 *     description="Default"
 * )
 *
 * @OA\Tag(
 *     name="Orders",
 *     description="Order creation and payment callbacks"
 * )
 *
 * @OA\Tag(
 *     name="Reports",
 *     description="Financial reporting endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Transactions",
 *     description="Ledger transactions"
 * )
 *
 * @OA\Tag(
 *     name="Wallets",
 *     description="Wallet operations"
 * )
 */
final class ApiDocumentation
{
}

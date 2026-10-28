<?php

use App\Jobs\SendOrderConfirmation;
use App\Mail\OrderConfirmationMail;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    $this->product = Product::factory()->create(['name' => 'Colgate Toothpaste', 'price' => '50.00', 'tax_percent' => 8, 'stock_on_hand' => 10]);
    $this->order = createOrder([[$this->product, 2]]);
});

it('is a queued job with retries and backoff', function () {
    $job = new SendOrderConfirmation($this->order);

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->tries)->toBe(3)
        ->and($job->backoff())->toBe([10, 30, 60]);
});

it('does not send anything during the request itself', function () {
    Mail::fake();

    placeOrder([[$this->product, 1]])->assertCreated();

    Mail::assertNothingSent();
    Queue::assertPushed(SendOrderConfirmation::class, 2);
});

it('emails the customer and logs it when the job runs', function () {
    Mail::fake();
    Log::spy();

    (new SendOrderConfirmation($this->order))->handle();

    Mail::assertSent(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail) => $mail->hasTo('thomas@example.com')
        && $mail->order->is($this->order));

    Log::shouldHaveReceived('info')->with('order.confirmation_sent', [
        'order_number' => $this->order->order_number,
        'email' => 'thomas@example.com',
        'grand_total' => '108.00',
    ]);
});

it('renders the bill in the email with a PDF attached', function () {
    $mail = new OrderConfirmationMail($this->order);

    $mail->assertHasSubject("Your bill {$this->order->order_number}")
        ->assertSeeInHtml('Colgate Toothpaste')
        ->assertSeeInHtml('108.00')
        ->assertSeeInHtml('A PDF copy of your bill is attached.');

    expect($mail->attachments())->toHaveCount(1);
});

it('can skip the PDF attachment through config', function () {
    config(['inventory.attach_pdf_bill' => false]);

    $mail = new OrderConfirmationMail($this->order);

    expect($mail->attachments())->toBeEmpty();
    $mail->assertDontSeeInHtml('PDF copy');
});

it('writes the mail to the log mailer end to end', function () {
    $path = storage_path('logs/mail-test.log');
    @unlink($path);

    config([
        'mail.default' => 'log',
        'mail.mailers.log.channel' => 'mail_test',
        'logging.channels.mail_test' => ['driver' => 'single', 'path' => $path],
        'inventory.attach_pdf_bill' => false,
    ]);

    (new SendOrderConfirmation($this->order))->handle();

    expect(file_get_contents($path))
        ->toContain('To: Thomas <thomas@example.com>')
        ->toContain("Subject: Your bill {$this->order->order_number}");

    unlink($path);
});

<?php


use App\Services\TaskScheduler\CoroutineReturnValue;
use App\Services\TaskScheduler\Scheduler;
use App\Services\TaskScheduler\SystemCall;
use App\Services\TaskScheduler\Task;

function task1()
{
    for ($i = 1; $i <= 10; ++$i) {
        echo "This is task 1 iteration $i.\n";
        yield;
    }
}

function task2()
{
    for ($i = 1; $i <= 5; ++$i) {
        echo "This is task 2 iteration $i.\n";
        yield;
    }
}

function getTaskId()
{
    return new SystemCall(function (Task $task, Scheduler $scheduler) {
        $task->setSendValue($task->getTaskId());
        $scheduler->schedule($task);
    });
}

function task()
{
    $tid = (yield getTaskId());
    $childTid = (yield newTask(childTask()));

    for ($i = 1; $i <= 6; ++$i) {
        echo "Parent task $tid iteration $i.\n";
        yield;

        if ($i == 3) yield killTask($childTid);
    }
}

function newTask(Generator $coroutine)
{
    return new SystemCall(
        function (Task $task, Scheduler $scheduler) use ($coroutine) {
            $task->setSendValue($scheduler->newTask($coroutine));
            $scheduler->schedule($task);
        }
    );
}

function killTask($tid)
{
    return new SystemCall(
        function (Task $task, Scheduler $scheduler) use ($tid) {
            $task->setSendValue($scheduler->killTask($tid));
            $scheduler->schedule($task);
        }
    );
}

function childTask()
{
    $tid = (yield getTaskId());
    while (true) {
        echo "Child task $tid still alive!\n";
        yield;
    }
}

function waitForRead($socket)
{
    return new SystemCall(
        function (Task $task, Scheduler $scheduler) use ($socket) {
            $scheduler->waitForRead($socket, $task);
        }
    );
}

function waitForWrite($socket)
{
    return new SystemCall(
        function (Task $task, Scheduler $scheduler) use ($socket) {
            $scheduler->waitForWrite($socket, $task);
        }
    );
}

function server($port)
{
    echo "Starting server at port $port...\n";

    $socket = @stream_socket_server("tcp://localhost:$port", $errNo, $errStr);
    if (!$socket) throw new Exception($errStr, $errNo);

    stream_set_blocking($socket, 0);

    while (true) {
        yield waitForRead($socket);
        $clientSocket = stream_socket_accept($socket, 0);
        yield newTask(handleClient($clientSocket));
    }
}

function handleClient($socket)
{
    yield waitForRead($socket);
    $data = fread($socket, 8192);

    $msg = "Received following request:\n\n$data";
    $msgLength = strlen($msg);

    $response = <<<RES
HTTP/1.1 200 OK\r
Content-Type: text/plain\r
Content-Length: $msgLength\r
Connection: close\r
\r
$msg
RES;

    yield waitForWrite($socket);
    fwrite($socket, $response);

    fclose($socket);
}

function echoTimes($msg, $max)
{
    for ($i = 1; $i <= $max; ++$i) {
        echo "$msg iteration $i\n";
        yield;
    }
}

/*function task()
{
    echoTimes('foo', 10); // print foo ten times
    echo "---\n";
    echoTimes('bar', 5); // print bar five times
    yield; // force it to be a coroutine
}*/
function retval($value) {
    return new CoroutineReturnValue($value);
}

function stackedCoroutine(Generator $gen) {
    $stack = new SplStack;

    for (;;) {
        $value = $gen->current();

        if ($value instanceof Generator) {
            $stack->push($gen);
            $gen = $value;
            continue;
        }

        $isReturnValue = $value instanceof CoroutineReturnValue;
        if (!$gen->valid() || $isReturnValue) {
            if ($stack->isEmpty()) {
                return;
            }

            $gen = $stack->pop();
            $gen->send($isReturnValue ? $value->getValue() : NULL);
            continue;
        }

        $gen->send(yield $gen->key() => $value);
    }
}

/*function server($port) {
    echo "Starting server at port $port...\n";

    $socket = @stream_socket_server("tcp://localhost:$port", $errNo, $errStr);
    if (!$socket) throw new Exception($errStr, $errNo);

    stream_set_blocking($socket, 0);

    $socket = new CoSocket($socket);
    while (true) {
        yield newTask(
            handleClient(yield $socket->accept())
        );
    }
}

function handleClient($socket) {
    $data = (yield $socket->read(8192));

    $msg = "Received following request:\n\n$data";
    $msgLength = strlen($msg);

    $response = <<<RES
HTTP/1.1 200 OK\r
Content-Type: text/plain\r
Content-Length: $msgLength\r
Connection: close\r
\r
$msg
RES;

    yield $socket->write($response);
    yield $socket->close();
}*/

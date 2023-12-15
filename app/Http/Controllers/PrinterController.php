<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use React\EventLoop\Factory;
use React\EventLoop\Loop;
use React\Socket\Connector;
use React\Socket\ConnectionInterface;

class PrinterController extends Controller
{
    private $ip;
    private $port;
    private $loop;
    private $connector;

    public function __construct()
    {
        $this->ip = '192.168.1.174'; // Replace with your printer's IP
        $this->port = 3000;

        $this->loop = Loop::get();
        $this->connector = new Connector($this->loop);
    }

    private function sendReceiveSingle($code, $callback)
    {
        $this->connector->connect("udp://{$this->ip}:{$this->port}")->then(
            function (ConnectionInterface $connection) use ($code, $callback) {
                $connection->write($code);
                $connection->on('data', function ($data) use ($connection, $callback) {
                    $callback($data);
                    $connection->close();
                });
            },
            function (\Exception $e) {
                // Handle exception
            }
        );

        $this->loop->run();
    }

    // Method to get printer firmware version.
    public function getVersion()
    {
        $this->sendReceiveSingle("M99999", function ($data) {
            $parts = explode(' ', $data);
            $version = explode(':', $parts[3])[1];
            return response()->json(['version' => $version]);
        });
    }

    // Method to get the printer's UID
    public function getID()
    {
        $this->sendReceiveSingle("M99999", function ($data) {
            $parts = explode(' ', $data);
            $uid = explode(':', $parts[4])[1];
            return response()->json(['uid' => $uid]);
        });
    }

    // Method to get the printer's name
    public function getName()
    {
        $this->sendReceiveSingle("M99999", function ($data) {
            $parts = explode(' ', $data);
            $name = explode(':', $parts[5])[1];
            return response()->json(['name' => $name]);
        });
    }

    // Method to list files on the printer's storage
    public function getCardFiles()
    {
        $this->sendReceiveSingle("M20", function ($data) {
            // You'll need to parse the response to extract file names and sizes
            // This parsing depends on the specific format of the printer's response
            return response()->json(['files' => $data]); // Modify this line as needed
        });
    }

    // Method to home the printer's axis
    public function homeAxis()
    {
        $this->sendReceiveSingle("G28 Z", function ($data) {
            return response()->json(['response' => $data]);
        });
    }

    // Method to get the current axis position
    public function getAxis()
    {
        $this->sendReceiveSingle("M114", function ($data) {
            // Parse the response to extract the axis position
            // Adjust the parsing as per your printer's response format
            $position = 0; // Replace with actual parsing logic
            return response()->json(['position' => $position]);
        });
    }

    // Method to stop the printer from printing.
    public function stopPrinting()
    {
        $this->sendReceiveSingle("M33", function ($data) {
            return response()->json(['response' => $data]);
        });
    }

    // Method to start print job.
    public function startPrinting(Request $request)
    {
        $filename = $request->input('filename');
        $this->sendReceiveSingle("M6030 '{$filename}'", function ($data) {
            return response()->json(['response' => $data]);
        });
    }

}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function create()
    {
        return view('events.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'organizer' => 'required',
            'tickets.*.ticket_no' => 'required_with:tickets.*',
            'tickets.*.price' => 'required_with:tickets.*|numeric|min:0',
        ]);

        $event = Event::create($request->except('tickets'));

        // Only process tickets if they exist
        if ($request->has('tickets') && is_array($request->tickets)) {
            foreach ($request->tickets as $ticket) {
                if (!empty($ticket['ticket_no']) && !empty($ticket['price'])) {
                    Ticket::create([
                        'event_id' => $event->id,
                        'ticket_no' => $ticket['ticket_no'],
                        'price' => $ticket['price'],
                    ]);
                }
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Event saved successfully!',
                'event_id' => $event->id
            ]);
        }

        return redirect()->back()->with('success', 'Event saved successfully!');
    }
}

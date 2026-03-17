<!DOCTYPE html>

<html>

<head>

    <title>Admin</title>

</head>

<body>

    <nav>

        <a href="{{ route('admin.dashboard') }}">Dashboard</a> |

        <a href="{{ route('admin.trips.index') }}">Trips</a> |

        <a href="{{ route('admin.users.index') }}">Users</a> |

        <a href="{{ route('admin.buses.index') }}">Buses</a> |

        <a href="{{ route('admin.routes.index') }}">Routes</a>


        <form method="POST" action="{{ route('logout') }}" style="display:inline">
            @csrf
            <button>Logout</button>
        </form>

    </nav>

    <hr>

    @yield('content')

</body>

</html>

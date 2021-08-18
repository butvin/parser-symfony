import http.cookiejar
import queue
import threading
import urllib.error
import urllib.parse
import urllib.request

test_url = 'http://www.google.com/humans.txt'
thread_number = 1000
timeout_value = 5


def process(task):
    cj = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(
        urllib.request.HTTPCookieProcessor(cj),
        urllib.request.HTTPRedirectHandler(),
        urllib.request.ProxyHandler({'http': task})
    )

    try:
        opener.open(test_url, timeout=timeout_value).read()
    except Exception as e:
        return None

    return task


class PrintThread(threading.Thread):
    def __init__(self, queue, filename):
        threading.Thread.__init__(self)
        self.queue = queue
        self.output = open(filename, 'a')
        self.shutdown = False

    def write(self, line):
        print(line, file=self.output)

    def run(self):
        while not self.shutdown:
            lines = self.queue.get()
            self.write(lines)
            self.queue.task_done()

    def terminate(self):
        self.output.close()
        self.shutdown = True


class ProcessThread(threading.Thread):
    def __init__(self, id, task_queue, out_queue):
        threading.Thread.__init__(self)
        self.task_queue = task_queue
        self.out_queue = out_queue
        self.id = id

    def run(self):
        while True:
            task = self.task_queue.get()
            result = process(task)

            if result is not None:
                self.out_queue.put(result)

            self.task_queue.task_done()


def check(proxies, output):
    input_queue = queue.Queue()
    result_queue = queue.Queue()

    workers = []
    for i in range(0, thread_number):
        t = ProcessThread(i, input_queue, result_queue)
        t.setDaemon(True)
        t.start()
        workers.append(t)

    f_printer = PrintThread(result_queue, output)
    f_printer.setDaemon(True)
    f_printer.start()

    for proxy in proxies:
        input_queue.put(proxy)

    if len(proxies) == 0:
        exit()

    input_queue.join()
    result_queue.join()

    f_printer.terminate()

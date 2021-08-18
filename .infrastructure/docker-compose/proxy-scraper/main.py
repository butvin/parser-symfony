import os
import logging
import time
from typing import Set
import proxy_checker
from checkerproxy_net import CheckerProxyArchive
from otherproxies import (aliveproxy, awmproxy, community_aliveproxy, hidemy,
                          hidester, httptunnel, openproxy, proxy11, proxy50_50,
                          proxy_ip_list, proxy_list_download)
from proxyscrape_all import ProxyScraper
from utils import filtrate_ports, prepare_proxy


def load_proxies() -> Set[str]:
    main_set: Set[str] = set()
    cpa = CheckerProxyArchive()
    ps = ProxyScraper()
    cpa_set, ps_set = cpa.parse_proxies(), ps.combine_results()
    p50_set, h_set = proxy50_50(), hidester()
    al_set, aw_set, op_set = aliveproxy(), awmproxy(), openproxy()
    hid_set, p11_set, ht_set = hidemy(), proxy11(), httptunnel()
    pld_set = proxy_list_download()
    return main_set.union(
        cpa_set,
        ps_set,
        p50_set,
        op_set,
        h_set,
        al_set,
        aw_set,
        pld_set,
        hid_set,
        p11_set,
        ht_set,
    )


def main() -> None:
    start = time.time()
    proxies_set = load_proxies()
    prepared_proxies = {
        f"{prepare_proxy(proxy)}\n"
        for proxy in proxies_set
        if filtrate_ports(proxy)
    }

    prepared_proxies = [proxy.rstrip('\n') for proxy in prepared_proxies]
    proxy_checker.check(prepared_proxies, os.getenv('SAVE_PATH'))

    logger.info(f"Program execution time: {time.time() - start: .2f} sec")


if __name__ == "__main__":
    logging.basicConfig(
        format="{asctime} <{levelname}> {name}: {message}",
        datefmt="%Y-%m-%d %H:%M:%S",
        style="{",
        level=logging.INFO,
    )
    logger = logging.getLogger("proxy_machine")

    main()

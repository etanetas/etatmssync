#!/usr/bin/python3
from lib.creep import Main
from config import Config

cfg = Config()
sync = Main()

sync.main(cfg.args)

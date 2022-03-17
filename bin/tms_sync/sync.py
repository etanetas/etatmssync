#!/usr/bin/python3
from lib.creep import Synchronizator
from config import Config

cfg = Config()
sync = Synchronizator()

sync.main(cfg.args)
